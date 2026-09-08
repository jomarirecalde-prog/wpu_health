<?php

use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UnifiedPortalController;
use App\Http\Controllers\Api\AppointmentApiController;
use App\Http\Controllers\Api\CalendarApiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Physician\AppointmentController as PhysicianAppointmentController;
use App\Http\Controllers\Physician\DashboardController as PhysicianDashboardController;
use App\Http\Controllers\Physician\ProfileController as PhysicianProfileController;
use App\Http\Controllers\Physician\ScheduleController as PhysicianScheduleController;
use App\Http\Controllers\Portal\AppointmentController as PortalAppointmentController;
use App\Http\Controllers\Portal\BookingController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\NotificationController as PortalNotificationController;
use App\Http\Controllers\Portal\PhysicianListController;
use App\Http\Controllers\Portal\ProfileController as PortalProfileController;
use App\Http\Controllers\Portal\PublicPortalController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

// Unified authentication entry point
Route::middleware('guest:admin,portal,physician')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:8,1')->name('login.store');
    Route::get('register', [AuthController::class, 'showLogin'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1')->name('register.store');
});

Route::post('logout', LogoutController::class)->middleware('auth:admin,portal,physician')->name('logout');

// Legacy login URLs → unified auth
Route::redirect('admin/login', '/login', 301);
Route::redirect('portal/login', '/login', 301);
Route::redirect('physician/login', '/login', 301);
Route::redirect('portal/register', '/register', 301);

// Legacy public portal bridge
Route::prefix('portal/legacy')->middleware('web')->group(function (): void {
    Route::any('index.php', [PublicPortalController::class, 'index'])->name('portal.legacy.home');
});

// Patient / User Portal
Route::prefix('portal')->middleware('web')->group(function (): void {
    Route::get('/', [PortalDashboardController::class, 'home'])->name('portal.home');
    Route::get('physicians', [PhysicianListController::class, 'index'])->name('portal.physicians');

    Route::middleware(['auth:portal', 'role:patient'])->group(function (): void {
        Route::get('dashboard', [PortalDashboardController::class, 'dashboard'])->name('portal.dashboard');

        Route::get('book', [BookingController::class, 'index'])->name('portal.book');
        Route::get('book/slots', [BookingController::class, 'slots'])->name('portal.book.slots');
        Route::post('book', [BookingController::class, 'store'])->name('portal.book.store');

        Route::get('appointments', [PortalAppointmentController::class, 'index'])->name('portal.appointments');
        Route::get('appointments/{appointment}', [PortalAppointmentController::class, 'show'])->name('portal.appointments.show');
        Route::get('calendar', [PortalAppointmentController::class, 'calendar'])->name('portal.calendar');

        Route::get('profile', [PortalProfileController::class, 'edit'])->name('portal.profile');
        Route::put('profile', [PortalProfileController::class, 'update'])->name('portal.profile.update');

        Route::get('notifications', [PortalNotificationController::class, 'index'])->name('portal.notifications');
        Route::post('notifications/{id}/read', [PortalNotificationController::class, 'markRead'])->name('portal.notifications.read');
    });
});

// Physician Portal
Route::prefix('physician')->middleware('web')->group(function (): void {
    Route::middleware(['auth:physician', 'role:physician'])->group(function (): void {
        Route::get('/', [PhysicianDashboardController::class, 'index'])->name('physician.dashboard');

        Route::get('appointments', [PhysicianAppointmentController::class, 'index'])->name('physician.appointments');
        Route::get('appointments/{appointment}', [PhysicianAppointmentController::class, 'show'])->name('physician.appointments.show');
        Route::post('appointments/{appointment}/confirm', [PhysicianAppointmentController::class, 'confirm'])->name('physician.appointments.confirm');
        Route::post('appointments/{appointment}/reject', [PhysicianAppointmentController::class, 'reject'])->name('physician.appointments.reject');
        Route::post('appointments/{appointment}/complete', [PhysicianAppointmentController::class, 'complete'])->name('physician.appointments.complete');
        Route::post('appointments/{appointment}/no-show', [PhysicianAppointmentController::class, 'noShow'])->name('physician.appointments.no-show');

        Route::get('schedule', [PhysicianScheduleController::class, 'index'])->name('physician.schedule');
        Route::post('schedule', [PhysicianScheduleController::class, 'store'])->name('physician.schedule.store');
        Route::post('schedule/exception', [PhysicianScheduleController::class, 'storeException'])->name('physician.schedule.exception');
        Route::get('calendar', [PhysicianScheduleController::class, 'calendar'])->name('physician.calendar');

        Route::get('profile', [PhysicianProfileController::class, 'edit'])->name('physician.profile');
        Route::put('profile', [PhysicianProfileController::class, 'update'])->name('physician.profile.update');
    });
});

Route::get('admin', function () {
    $roles = app(\App\Services\AuthRoleService::class);

    return $roles->hasRole('admin', 'super_admin')
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
})->name('admin.index');

Route::middleware(['auth:admin', 'role:admin,super_admin'])->prefix('admin')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('admin.dashboard');

    Route::prefix('calendar')->name('admin.calendar.')->group(function (): void {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('appointments', [CalendarController::class, 'appointments'])->name('appointments');
        Route::post('appointments', [CalendarController::class, 'createAppointment'])->name('appointments.store');
        Route::get('physicians', [CalendarController::class, 'physicians'])->name('physicians');
        Route::post('physicians', [CalendarController::class, 'storePhysician'])->name('physicians.store');
        Route::get('portal-users', [CalendarController::class, 'portalUsers'])->name('portal-users');
        Route::get('reports', [CalendarController::class, 'reports'])->name('reports');
        Route::get('settings', [CalendarController::class, 'settings'])->name('settings');
        Route::put('settings', [CalendarController::class, 'updateSettings'])->name('settings.update');
    });

    Route::prefix('workspace')->group(function (): void {
        Route::get('exit', function () {
            auth('admin')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();

            return redirect()->route('home');
        })->name('admin.workspace.exit');

        Route::any('{path?}', UnifiedPortalController::class)
            ->where('path', '.*')
            ->name('admin.workspace');
    });
});

// Calendar & Appointment API
Route::prefix('api')->middleware('web')->group(function (): void {
    Route::middleware('auth:admin,physician,portal')->group(function (): void {
        Route::get('calendar/feed', [CalendarApiController::class, 'feed']);
        Route::get('calendar/date-detail', [CalendarApiController::class, 'dateDetail']);
        Route::get('calendar/appointments', [CalendarApiController::class, 'appointments']);
        Route::get('physicians/{physician}/availability', [CalendarApiController::class, 'availability']);
        Route::get('physicians/{physician}/schedule', [CalendarApiController::class, 'schedule']);
        Route::get('appointments/{appointment}', [AppointmentApiController::class, 'show']);
    });

    Route::middleware('auth:portal')->group(function (): void {
        Route::post('appointments', [AppointmentApiController::class, 'store']);
        Route::post('appointments/{appointment}/cancel', [AppointmentApiController::class, 'cancel']);
        Route::post('appointments/{appointment}/reschedule', [AppointmentApiController::class, 'reschedule']);
    });

    Route::middleware('auth:admin,physician')->group(function (): void {
        Route::post('appointments/{appointment}/confirm', [AppointmentApiController::class, 'confirm']);
        Route::post('appointments/{appointment}/reject', [AppointmentApiController::class, 'reject']);
        Route::post('appointments/{appointment}/complete', [AppointmentApiController::class, 'complete']);
        Route::post('appointments/{appointment}/no-show', [AppointmentApiController::class, 'noShow']);
    });

    Route::middleware('auth:admin')->group(function (): void {
        Route::post('appointments/{appointment}/cancel', [AppointmentApiController::class, 'cancel']);
        Route::post('appointments/{appointment}/reschedule', [AppointmentApiController::class, 'reschedule']);
        Route::post('calendar/appointments', [CalendarApiController::class, 'adminStore']);
    });
});
