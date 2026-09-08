<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Appointments\AppointmentNotificationService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function home(): View
    {
        return view('portal.home');
    }

    public function dashboard(AppointmentNotificationService $notifications): View
    {
        $user = auth('portal')->user();
        $next = Appointment::query()
            ->with('physician')
            ->where('portal_user_id', $user->id)
            ->whereDate('appointment_date', '>=', today())
            ->whereIn('status', ['pending', 'confirmed'])
            ->orderBy('appointment_date')
            ->orderBy('start_time')
            ->first();

        return view('portal.dashboard', [
            'nextAppointment' => $next,
            'recentAppointments' => Appointment::query()
                ->with('physician')
                ->where('portal_user_id', $user->id)
                ->orderByDesc('appointment_date')
                ->limit(5)
                ->get(),
            'notifications' => $notifications->getRecent('portal_user', $user->id),
            'unreadCount' => $notifications->getUnreadCount('portal_user', $user->id),
        ]);
    }
}
