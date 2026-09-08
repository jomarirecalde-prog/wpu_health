<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Physician;
use App\Models\PortalUser;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\CalendarFeedService;
use App\Services\Appointments\SlotGenerationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarApiController extends Controller
{
    public function feed(Request $request, CalendarFeedService $feedService): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'physician_id' => 'nullable|integer|exists:physicians,id',
            'department_id' => 'nullable|integer',
            'status' => 'nullable|string',
            'consultation_type' => 'nullable|string',
        ]);

        [$role, $filters] = $this->resolveContext($request);

        $events = $feedService->buildFeed(
            Carbon::parse($request->start)->startOfDay(),
            Carbon::parse($request->end)->startOfDay(),
            $filters,
            $role
        );

        if ($role === 'portal') {
            $events = array_map(function (array $event) {
                if (($event['extendedProps']['kind'] ?? '') === 'appointment') {
                    $event['title'] = $event['extendedProps']['physician_name'] ?: $event['title'];
                }

                return $event;
            }, $events);
        }

        return response()->json(['data' => $events]);
    }

    public function dateDetail(Request $request, CalendarFeedService $feedService): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
            'physician_id' => 'nullable|integer|exists:physicians,id',
            'department_id' => 'nullable|integer',
            'status' => 'nullable|string',
            'consultation_type' => 'nullable|string',
        ]);

        [$role, $filters] = $this->resolveContext($request);

        return response()->json([
            'data' => $feedService->getDateDetail(
                Carbon::parse($request->date('date')),
                $filters,
                $role
            ),
        ]);
    }

    public function appointments(Request $request): JsonResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
            'physician_id' => 'nullable|integer|exists:physicians,id',
            'department_id' => 'nullable|integer',
            'status' => 'nullable|string',
            'consultation_type' => 'nullable|string',
            'patient' => 'nullable|string',
            'portal_user_id' => 'nullable|integer',
        ]);

        [$role, $filters] = $this->resolveContext($request);
        $feedService = app(CalendarFeedService::class);

        $events = $feedService->buildFeed(
            Carbon::parse($request->start)->startOfDay(),
            Carbon::parse($request->end)->startOfDay(),
            $filters,
            $role
        );

        $appointments = array_values(array_filter(
            $events,
            fn (array $event) => ($event['extendedProps']['kind'] ?? null) === 'appointment'
        ));

        return response()->json(['data' => $appointments]);
    }

    public function availability(int $physicianId, Request $request, SlotGenerationService $slotService): JsonResponse
    {
        $request->validate(['date' => 'required|date|after_or_equal:today']);

        $physician = Physician::query()->findOrFail($physicianId);
        $date = Carbon::parse($request->date('date'));
        $slots = $slotService->getAvailableSlots($physician, $date);

        return response()->json([
            'physician_id' => $physician->id,
            'date' => $date->format('Y-m-d'),
            'slots' => $slots,
        ]);
    }

    public function schedule(int $physicianId): JsonResponse
    {
        $physician = Physician::query()
            ->with(['schedules', 'scheduleExceptions' => fn ($q) => $q->where('date', '>=', today())])
            ->findOrFail($physicianId);

        return response()->json(['data' => $physician]);
    }

    public function adminStore(Request $request, AppointmentBookingService $bookingService): JsonResponse
    {
        $validated = $request->validate([
            'portal_user_id' => 'required|integer|exists:portal_users,id',
            'physician_id' => 'required|integer|exists:physicians,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i:s',
            'consultation_type' => 'required|string|max:100',
            'reason' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
        ]);

        $user = PortalUser::query()->findOrFail($validated['portal_user_id']);
        $admin = $request->user('admin');
        $appointment = $bookingService->book($validated, $user, $admin?->username ?? 'admin');

        return response()->json([
            'message' => 'Appointment booked successfully.',
            'data' => $appointment->load(['physician', 'portalUser']),
        ], 201);
    }

    /**
     * @return array{0: string, 1: array<string, mixed>}
     */
    private function resolveContext(Request $request): array
    {
        $filters = $request->only([
            'physician_id',
            'department_id',
            'status',
            'consultation_type',
        ]);

        if ($request->user('admin')) {
            return ['admin', $filters];
        }

        if ($physician = $request->user('physician')) {
            $filters['physician_id'] = $physician->id;

            return ['physician', $filters];
        }

        if ($portal = $request->user('portal')) {
            $filters['portal_user_id'] = $portal->id;

            return ['portal', $filters];
        }

        abort(401);
    }
}
