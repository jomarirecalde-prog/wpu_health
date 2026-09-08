<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\AppointmentStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentApiController extends Controller
{
    public function store(Request $request, AppointmentBookingService $bookingService): JsonResponse
    {
        $validated = $request->validate([
            'physician_id' => 'required|integer|exists:physicians,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i:s',
            'consultation_type' => 'required|string|max:100',
            'reason' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'portal_user_id' => 'nullable|integer|exists:portal_users,id',
        ]);

        $user = $request->user('portal');
        if ($user === null && $request->filled('portal_user_id')) {
            abort(403, 'Admin must use admin appointment creation endpoint.');
        }

        if ($user === null) {
            abort(401);
        }

        $appointment = $bookingService->book($validated, $user);

        return response()->json([
            'message' => 'Appointment booked successfully.',
            'data' => $appointment,
        ], 201);
    }

    public function show(Appointment $appointment, Request $request): JsonResponse
    {
        $this->authorizeAppointmentAccess($appointment, $request);

        $appointment->load(['physician', 'portalUser', 'history', 'patientRecord']);

        return response()->json([
            'data' => $appointment,
            'meta' => [
                'allowed_actions' => $this->resolveAllowedActions($appointment, $request),
                'consultation_type_label' => config('appointments.consultation_types')[$appointment->consultation_type] ?? $appointment->consultation_type,
            ],
        ]);
    }

    public function confirm(Appointment $appointment, Request $request, AppointmentStatusService $statusService): JsonResponse
    {
        $actor = $this->resolveActor($request);
        $type = $this->resolveActorType($request);

        $updated = $statusService->confirm($appointment, $actor, $type);

        return response()->json(['message' => 'Appointment confirmed.', 'data' => $updated]);
    }

    public function reject(Appointment $appointment, Request $request, AppointmentStatusService $statusService): JsonResponse
    {
        $validated = $request->validate(['reason' => 'required|string|max:1000']);
        $actor = $this->resolveActor($request);
        $type = $this->resolveActorType($request);

        $updated = $statusService->reject($appointment, $actor, $validated['reason'], $type);

        return response()->json(['message' => 'Appointment rejected.', 'data' => $updated]);
    }

    public function cancel(Appointment $appointment, Request $request, AppointmentStatusService $statusService): JsonResponse
    {
        $validated = $request->validate(['reason' => 'nullable|string|max:1000']);
        $actor = $this->resolveActor($request);
        $type = $this->resolveActorType($request);

        $updated = $statusService->cancel($appointment, $actor, $validated['reason'] ?? null, $type);

        return response()->json(['message' => 'Appointment cancelled.', 'data' => $updated]);
    }

    public function reschedule(Appointment $appointment, Request $request, AppointmentStatusService $statusService): JsonResponse
    {
        $validated = $request->validate([
            'physician_id' => 'nullable|integer|exists:physicians,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:H:i:s',
            'reason' => 'nullable|string|max:1000',
        ]);

        $actor = $this->resolveActor($request);
        $type = $this->resolveActorType($request);

        $updated = $statusService->reschedule($appointment, $validated, $actor, $type);

        return response()->json(['message' => 'Appointment rescheduled.', 'data' => $updated]);
    }

    public function complete(Appointment $appointment, Request $request, AppointmentStatusService $statusService): JsonResponse
    {
        $actor = $this->resolveActor($request);
        $type = $this->resolveActorType($request);

        $updated = $statusService->complete($appointment, $actor, $type);

        return response()->json(['message' => 'Appointment completed.', 'data' => $updated]);
    }

    public function noShow(Appointment $appointment, Request $request, AppointmentStatusService $statusService): JsonResponse
    {
        $actor = $this->resolveActor($request);
        $type = $this->resolveActorType($request);

        $updated = $statusService->markNoShow($appointment, $actor, $type);

        return response()->json(['message' => 'Marked as no-show.', 'data' => $updated]);
    }

    private function resolveActor(Request $request): string
    {
        if ($request->user('admin')) {
            return $request->user('admin')->username;
        }
        if ($request->user('physician')) {
            return $request->user('physician')->email;
        }
        if ($request->user('portal')) {
            return $request->user('portal')->email;
        }

        abort(401);
    }

    private function resolveActorType(Request $request): string
    {
        if ($request->user('admin')) {
            return 'admin';
        }
        if ($request->user('physician')) {
            return 'physician';
        }

        return 'portal_user';
    }

    private function authorizeAppointmentAccess(Appointment $appointment, Request $request): void
    {
        if ($request->user('admin')) {
            return;
        }

        if ($physician = $request->user('physician')) {
            abort_unless($appointment->physician_id === $physician->id, 403);

            return;
        }

        if ($portal = $request->user('portal')) {
            abort_unless($appointment->portal_user_id === $portal->id, 403);

            return;
        }

        abort(401);
    }

    /**
     * @return list<string>
     */
    private function resolveAllowedActions(Appointment $appointment, Request $request): array
    {
        $actions = ['view'];
        $status = $appointment->status;

        if ($request->user('admin')) {
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Confirmed)) {
                $actions[] = 'confirm';
            }
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Rejected)) {
                $actions[] = 'reject';
            }
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Cancelled)) {
                $actions[] = 'cancel';
            }
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Rescheduled)) {
                $actions[] = 'reschedule';
            }
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Completed)) {
                $actions[] = 'complete';
            }
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::NoShow)) {
                $actions[] = 'no_show';
            }

            return $actions;
        }

        if ($request->user('physician')) {
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Confirmed)) {
                $actions[] = 'confirm';
            }
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Completed)) {
                $actions[] = 'complete';
            }
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::NoShow)) {
                $actions[] = 'no_show';
            }

            return $actions;
        }

        if ($request->user('portal')) {
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Cancelled)) {
                $actions[] = 'cancel';
            }
            if ($status->canTransitionTo(\App\Enums\AppointmentStatus::Rescheduled)) {
                $actions[] = 'reschedule';
            }
        }

        return $actions;
    }
}
