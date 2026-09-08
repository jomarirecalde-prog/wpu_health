<?php

namespace App\Http\Controllers\Physician;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentStatusService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(Request $request): View
    {
        $physician = auth('physician')->user();
        $query = Appointment::query()
            ->with('portalUser')
            ->where('physician_id', $physician->id)
            ->orderByDesc('appointment_date');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return view('physician.appointments.index', [
            'appointments' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function show(Appointment $appointment): View
    {
        abort_unless($appointment->physician_id === auth('physician')->id(), 403);
        $appointment->load(['portalUser', 'history']);

        return view('physician.appointments.show', compact('appointment'));
    }

    public function confirm(Appointment $appointment, AppointmentStatusService $service): \Illuminate\Http\RedirectResponse
    {
        abort_unless($appointment->physician_id === auth('physician')->id(), 403);
        $service->confirm($appointment, auth('physician')->user()->email, 'physician');

        return back()->with('status', 'Appointment confirmed.');
    }

    public function reject(Request $request, Appointment $appointment, AppointmentStatusService $service): \Illuminate\Http\RedirectResponse
    {
        abort_unless($appointment->physician_id === auth('physician')->id(), 403);
        $validated = $request->validate(['reason' => 'required|string|max:1000']);
        $service->reject($appointment, auth('physician')->user()->email, $validated['reason'], 'physician');

        return back()->with('status', 'Appointment rejected.');
    }

    public function complete(Appointment $appointment, AppointmentStatusService $service): \Illuminate\Http\RedirectResponse
    {
        abort_unless($appointment->physician_id === auth('physician')->id(), 403);
        $service->complete($appointment, auth('physician')->user()->email, 'physician');

        return back()->with('status', 'Consultation marked as completed.');
    }

    public function noShow(Appointment $appointment, AppointmentStatusService $service): \Illuminate\Http\RedirectResponse
    {
        abort_unless($appointment->physician_id === auth('physician')->id(), 403);
        $service->markNoShow($appointment, auth('physician')->user()->email, 'physician');

        return back()->with('status', 'Marked as no-show.');
    }
}
