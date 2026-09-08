<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function index(): View
    {
        $appointments = Appointment::query()
            ->with('physician')
            ->where('portal_user_id', auth('portal')->id())
            ->orderByDesc('appointment_date')
            ->paginate(15);

        return view('portal.appointments.index', compact('appointments'));
    }

    public function show(Appointment $appointment): View
    {
        abort_unless($appointment->portal_user_id === auth('portal')->id(), 403);
        $appointment->load(['physician', 'history']);

        return view('portal.appointments.show', compact('appointment'));
    }

    public function calendar(): View
    {
        return view('portal.calendar');
    }
}
