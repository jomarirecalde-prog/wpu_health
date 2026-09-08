<?php

namespace App\Http\Controllers\Physician;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentNotificationService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(AppointmentNotificationService $notifications): View
    {
        $physician = auth('physician')->user();

        return view('physician.dashboard', [
            'todayAppointments' => Appointment::query()
                ->with('portalUser')
                ->where('physician_id', $physician->id)
                ->whereDate('appointment_date', today())
                ->orderBy('start_time')
                ->get(),
            'upcoming' => Appointment::query()
                ->with('portalUser')
                ->where('physician_id', $physician->id)
                ->whereDate('appointment_date', '>', today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->orderBy('appointment_date')
                ->limit(10)
                ->get(),
            'notifications' => $notifications->getRecent('physician', $physician->id),
        ]);
    }
}
