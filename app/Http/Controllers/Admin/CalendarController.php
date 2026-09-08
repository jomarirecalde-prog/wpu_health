<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Department;
use App\Models\PatientType;
use App\Models\Physician;
use App\Models\PortalUser;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\AppointmentStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function index(AppointmentStatsService $statsService): View
    {
        return view('admin.calendar.index', [
            'physicians' => Physician::query()->where('status', 'active')->orderBy('name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
            'consultationTypes' => config('appointments.consultation_types'),
            'portalUsers' => PortalUser::query()->where('status', 'active')->orderBy('name')->get(),
            'stats' => $statsService->getDashboardStats(),
        ]);
    }

    public function appointments(Request $request): View
    {
        $query = Appointment::query()
            ->with(['physician', 'portalUser'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('physician_id')) {
            $query->where('physician_id', $request->integer('physician_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('appointment_date', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('appointment_date', '<=', $request->date('date_to'));
        }

        return view('admin.calendar.appointments', [
            'appointments' => $query->paginate(25)->withQueryString(),
            'physicians' => Physician::query()->orderBy('name')->get(),
        ]);
    }

    public function physicians(): View
    {
        return view('admin.calendar.physicians', [
            'physicians' => Physician::query()->orderBy('name')->paginate(20),
            'departments' => Department::query()->orderBy('name')->get(),
            'staffSignatures' => \App\Models\StaffSignature::query()->orderBy('name')->get(),
            'consultationTypes' => config('appointments.consultation_types'),
        ]);
    }

    public function storePhysician(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:physicians,email',
            'password' => 'required|string|min:8|confirmed',
            'professional_title' => 'nullable|string|max:100',
            'specialization' => 'nullable|string|max:255',
            'license_no' => 'nullable|string|max:100',
            'department_id' => 'nullable|integer',
            'consultation_location' => 'nullable|string|max:255',
            'consultation_duration' => 'nullable|integer|min:15|max:120',
            'max_daily_appointments' => 'nullable|integer|min:1|max:100',
            'staff_signature_id' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
        ]);

        Physician::query()->create($validated);

        return back()->with('status', 'Physician added successfully.');
    }

    public function portalUsers(): View
    {
        return view('admin.calendar.portal-users', [
            'users' => PortalUser::query()->orderByDesc('created_at')->paginate(25),
            'patientTypes' => PatientType::query()->orderBy('type_name')->get(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function reports(Request $request): View
    {
        $query = Appointment::query()->with(['physician', 'portalUser']);

        if ($request->filled('date_from')) {
            $query->whereDate('appointment_date', '>=', $request->date('date_from'));
        } else {
            $query->whereDate('appointment_date', '>=', now()->startOfMonth());
        }
        if ($request->filled('date_to')) {
            $query->whereDate('appointment_date', '<=', $request->date('date_to'));
        }
        if ($request->filled('physician_id')) {
            $query->where('physician_id', $request->integer('physician_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $appointments = $query->orderBy('appointment_date')->get();

        return view('admin.calendar.reports', [
            'appointments' => $appointments,
            'physicians' => Physician::query()->orderBy('name')->get(),
            'summary' => [
                'total' => $appointments->count(),
                'completed' => $appointments->filter(fn ($a) => $a->status === AppointmentStatus::Completed)->count(),
                'cancelled' => $appointments->filter(fn ($a) => $a->status === AppointmentStatus::Cancelled)->count(),
                'no_show' => $appointments->filter(fn ($a) => $a->status === AppointmentStatus::NoShow)->count(),
            ],
        ]);
    }

    public function settings(): View
    {
        $settings = app(\App\Services\Appointments\AppointmentSettingsService::class)->all();

        return view('admin.calendar.settings', ['settings' => $settings]);
    }

    public function updateSettings(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'cancellation_hours_before' => 'required|integer|min:0|max:168',
            'booking_deadline_hours' => 'required|integer|min:0|max:72',
            'reminder_hours' => 'required|string',
            'default_consultation_duration' => 'required|integer|min:15|max:120',
        ]);

        $service = app(\App\Services\Appointments\AppointmentSettingsService::class);
        foreach ($validated as $key => $value) {
            $service->set($key, $value);
        }

        return back()->with('status', 'Appointment settings updated.');
    }

    public function createAppointment(Request $request, AppointmentBookingService $bookingService): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'portal_user_id' => 'required|integer|exists:portal_users,id',
            'physician_id' => 'required|integer|exists:physicians,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i:s',
            'consultation_type' => 'required|string',
            'reason' => 'nullable|string|max:2000',
        ]);

        $user = PortalUser::query()->findOrFail($validated['portal_user_id']);
        $bookingService->book($validated, $user, auth('admin')->user()->username);

        return back()->with('status', 'Appointment created successfully.');
    }
}
