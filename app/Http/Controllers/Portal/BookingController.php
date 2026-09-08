<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Physician;
use App\Services\Appointments\AppointmentBookingService;
use App\Services\Appointments\SlotGenerationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(): View
    {
        return view('portal.booking.index', [
            'physicians' => Physician::query()->where('status', 'active')->orderBy('name')->get(),
            'consultationTypes' => config('appointments.consultation_types'),
        ]);
    }

    public function slots(Request $request, SlotGenerationService $slotService): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'physician_id' => 'required|integer|exists:physicians,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $physician = Physician::query()->findOrFail($validated['physician_id']);
        $slots = $slotService->getAvailableSlots($physician, Carbon::parse($validated['date']));

        return response()->json(['slots' => $slots]);
    }

    public function store(Request $request, AppointmentBookingService $bookingService): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'physician_id' => 'required|integer|exists:physicians,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'consultation_type' => 'required|string',
            'reason' => 'required|string|max:2000',
        ]);

        if (strlen($validated['start_time']) === 5) {
            $validated['start_time'] .= ':00';
        }

        $appointment = $bookingService->book($validated, auth('portal')->user());

        return redirect()->route('portal.appointments.show', $appointment)
            ->with('status', 'Appointment successfully booked!');
    }
}
