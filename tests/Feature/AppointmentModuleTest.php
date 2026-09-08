<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Physician;
use App\Models\PhysicianSchedule;
use App\Models\PortalUser;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\AppointmentStatusService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AppointmentModuleTest extends TestCase
{
    use RefreshDatabase;

    private PortalUser $patient;

    private Physician $physician;

    protected function setUp(): void
    {
        parent::setUp();

        $this->patient = PortalUser::factory()->create([
            'email' => 'test@example.com',
            'status' => 'active',
        ]);

        $this->physician = Physician::factory()->create([
            'consultation_duration' => 30,
            'max_daily_appointments' => 10,
            'status' => 'active',
        ]);

        $day = Carbon::tomorrow()->dayOfWeek;
        PhysicianSchedule::query()->create([
            'physician_id' => $this->physician->id,
            'day_of_week' => $day,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_break' => false,
        ]);
    }

    public function test_portal_user_registration_and_login(): void
    {
        $response = $this->post('/portal/register', [
            'name' => 'New Patient',
            'email' => 'new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'contact_number' => '09170000000',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $this->assertDatabaseHas('portal_users', ['email' => 'new@example.com']);
    }

    public function test_appointment_booking_creates_pending_appointment(): void
    {
        $date = Carbon::tomorrow();
        while ($date->dayOfWeek === 0 || $date->dayOfWeek === 6) {
            $date->addDay();
        }

        PhysicianSchedule::query()->where('physician_id', $this->physician->id)->delete();
        PhysicianSchedule::query()->create([
            'physician_id' => $this->physician->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_break' => false,
        ]);

        $booking = app(AppointmentBookingService::class);
        $appointment = $booking->book([
            'physician_id' => $this->physician->id,
            'appointment_date' => $date->format('Y-m-d'),
            'start_time' => '09:00:00',
            'consultation_type' => 'general',
            'reason' => 'Checkup',
        ], $this->patient);

        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertStringStartsWith('WPU-', $appointment->appointment_number);
        $this->assertDatabaseHas('appointment_history', [
            'appointment_id' => $appointment->id,
            'action' => 'created',
        ]);
    }

    public function test_double_booking_is_prevented(): void
    {
        $date = Carbon::tomorrow();
        while ($date->dayOfWeek === 0 || $date->dayOfWeek === 6) {
            $date->addDay();
        }

        PhysicianSchedule::query()->where('physician_id', $this->physician->id)->delete();
        PhysicianSchedule::query()->create([
            'physician_id' => $this->physician->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_break' => false,
        ]);

        $booking = app(AppointmentBookingService::class);
        $data = [
            'physician_id' => $this->physician->id,
            'appointment_date' => $date->format('Y-m-d'),
            'start_time' => '10:00:00',
            'consultation_type' => 'general',
            'reason' => 'First',
        ];

        $booking->book($data, $this->patient);
        $other = PortalUser::factory()->create();

        $this->expectException(ValidationException::class);
        $booking->book($data, $other);
    }

    public function test_status_transitions(): void
    {
        $appointment = Appointment::factory()->create([
            'portal_user_id' => $this->patient->id,
            'physician_id' => $this->physician->id,
            'status' => AppointmentStatus::Pending,
        ]);

        $status = app(AppointmentStatusService::class);
        $confirmed = $status->confirm($appointment, 'admin@test', 'admin');
        $this->assertSame(AppointmentStatus::Confirmed, $confirmed->status);

        $completed = $status->complete($confirmed, 'dr@test', 'physician');
        $this->assertSame(AppointmentStatus::Completed, $completed->status);
    }

    public function test_patient_cannot_view_other_appointments(): void
    {
        $other = PortalUser::factory()->create();
        $appointment = Appointment::factory()->create([
            'portal_user_id' => $other->id,
            'physician_id' => $this->physician->id,
        ]);

        $this->actingAs($this->patient, 'portal')
            ->get(route('portal.appointments.show', $appointment))
            ->assertForbidden();
    }

    public function test_calendar_feed_returns_appointments_and_schedule_overlays(): void
    {
        $date = Carbon::tomorrow();
        while ($date->dayOfWeek === 0 || $date->dayOfWeek === 6) {
            $date->addDay();
        }

        PhysicianSchedule::query()->where('physician_id', $this->physician->id)->delete();
        PhysicianSchedule::query()->create([
            'physician_id' => $this->physician->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'is_break' => false,
        ]);
        PhysicianSchedule::query()->create([
            'physician_id' => $this->physician->id,
            'day_of_week' => $date->dayOfWeek,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'is_break' => true,
        ]);

        Appointment::factory()->create([
            'portal_user_id' => $this->patient->id,
            'physician_id' => $this->physician->id,
            'appointment_date' => $date->format('Y-m-d'),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => AppointmentStatus::Confirmed,
        ]);

        $feedService = app(\App\Services\Appointments\CalendarFeedService::class);
        $events = $feedService->buildFeed(
            $date->copy()->startOfMonth(),
            $date->copy()->endOfMonth(),
            ['physician_id' => $this->physician->id],
            'physician'
        );

        $kinds = collect($events)->pluck('extendedProps.kind')->unique()->values()->all();
        $this->assertContains('appointment', $kinds);
        $this->assertContains('available', $kinds);
        $this->assertContains('break', $kinds);

        $detail = $feedService->getDateDetail(
            $date,
            ['physician_id' => $this->physician->id],
            'physician'
        );

        $this->assertSame($this->patient->name, $detail['appointments'][0]['patient_name']);
        $this->assertTrue($detail['schedule']['is_working_day']);
    }
}
