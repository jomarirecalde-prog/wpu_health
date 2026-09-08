<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\AppointmentNotification;
use App\Services\Appointments\AppointmentNotificationService;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(AppointmentNotificationService $service): View
    {
        $user = auth('portal')->user();

        return view('portal.notifications.index', [
            'notifications' => $service->getRecent('portal_user', $user->id, 50),
        ]);
    }

    public function markRead(int $id): \Illuminate\Http\JsonResponse
    {
        $notification = AppointmentNotification::query()
            ->where('notifiable_type', 'portal_user')
            ->where('notifiable_id', auth('portal')->id())
            ->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }
}
