<?php

namespace App\Http\Controllers\Physician;

use App\Http\Controllers\Controller;
use App\Models\PhysicianSchedule;
use App\Models\PhysicianScheduleException;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(): View
    {
        $physician = auth('physician')->user()->load(['schedules', 'scheduleExceptions']);

        return view('physician.schedule.index', [
            'physician' => $physician,
            'days' => ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $physician = auth('physician')->user();
        $validated = $request->validate([
            'schedules' => 'required|array',
            'schedules.*.day_of_week' => 'required|integer|min:0|max:6',
            'schedules.*.start_time' => 'required|date_format:H:i',
            'schedules.*.end_time' => 'required|date_format:H:i',
            'schedules.*.is_break' => 'nullable|boolean',
        ]);

        PhysicianSchedule::query()->where('physician_id', $physician->id)->delete();

        foreach ($validated['schedules'] as $entry) {
            PhysicianSchedule::query()->create([
                'physician_id' => $physician->id,
                'day_of_week' => $entry['day_of_week'],
                'start_time' => $entry['start_time'].':00',
                'end_time' => $entry['end_time'].':00',
                'is_break' => ! empty($entry['is_break']),
                'is_available' => true,
            ]);
        }

        return back()->with('status', 'Schedule saved successfully.');
    }

    public function storeException(Request $request): \Illuminate\Http\RedirectResponse
    {
        $physician = auth('physician')->user();
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'type' => 'required|in:unavailable,blocked,leave,meeting,holiday,special_schedule',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'reason' => 'nullable|string|max:500',
        ]);

        PhysicianScheduleException::query()->create([
            'physician_id' => $physician->id,
            'date' => $validated['date'],
            'start_time' => isset($validated['start_time']) ? $validated['start_time'].':00' : null,
            'end_time' => isset($validated['end_time']) ? $validated['end_time'].':00' : null,
            'type' => $validated['type'],
            'reason' => $validated['reason'] ?? null,
            'created_by' => $physician->email,
        ]);

        return back()->with('status', 'Availability exception saved.');
    }

    public function calendar(): View
    {
        return view('physician.calendar', [
            'physician' => auth('physician')->user(),
        ]);
    }
}
