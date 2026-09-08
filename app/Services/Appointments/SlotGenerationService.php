<?php

namespace App\Services\Appointments;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Physician;
use App\Models\PhysicianSchedule;
use App\Models\PhysicianScheduleException;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlotGenerationService
{
    public function __construct(
        private readonly AppointmentSettingsService $settings
    ) {}

    /**
     * @return list<array{start: string, end: string, label: string}>
     */
    public function getAvailableSlots(Physician $physician, Carbon $date): array
    {
        if (! $physician->isActive()) {
            return [];
        }

        if ($date->isPast() && ! $date->isToday()) {
            return [];
        }

        $duration = $physician->consultation_duration ?: $this->settings->get('default_consultation_duration', 30);
        $windows = $this->getConsultationWindows($physician, $date);

        if ($windows->isEmpty()) {
            return [];
        }

        $booked = $this->getBookedSlots($physician, $date);
        $dailyCount = $this->getDailyAppointmentCount($physician, $date);
        $maxDaily = $physician->max_daily_appointments;

        $slots = [];
        foreach ($windows as $window) {
            $cursor = Carbon::parse($date->format('Y-m-d').' '.$window['start']);
            $windowEnd = Carbon::parse($date->format('Y-m-d').' '.$window['end']);

            while ($cursor->copy()->addMinutes($duration)->lte($windowEnd)) {
                $slotStart = $cursor->format('H:i:s');
                $slotEnd = $cursor->copy()->addMinutes($duration)->format('H:i:s');

                if ($this->isSlotBookable($date, $slotStart, $booked, $dailyCount, $maxDaily)) {
                    $slots[] = [
                        'start' => $slotStart,
                        'end' => $slotEnd,
                        'label' => $cursor->format('g:i A').' – '.$cursor->copy()->addMinutes($duration)->format('g:i A'),
                    ];
                }

                $cursor->addMinutes($duration);
            }
        }

        return $slots;
    }

    /**
     * Schedule metadata for calendar overlays and date detail panels.
     *
     * @return array{
     *     work_blocks: list<array{start: string, end: string, label: string}>,
     *     breaks: list<array{start: string, end: string, label: string, reason: ?string}>,
     *     blocks: list<array{start: ?string, end: ?string, label: string, type: string, reason: ?string, all_day: bool}>,
     *     available_windows: list<array{start: string, end: string, label: string}>,
     *     available_slots: list<array{start: string, end: string, label: string}>,
     *     is_working_day: bool
     * }
     */
    public function getScheduleMetadata(Physician $physician, Carbon $date): array
    {
        $dayOfWeek = (int) $date->dayOfWeek;
        $exceptions = PhysicianScheduleException::query()
            ->where('physician_id', $physician->id)
            ->whereDate('date', $date->format('Y-m-d'))
            ->get();

        foreach ($exceptions as $exception) {
            if (in_array($exception->type, ['unavailable', 'blocked', 'leave', 'holiday', 'meeting'], true)
                && $exception->start_time === null
                && $exception->end_time === null) {
                return [
                    'work_blocks' => [],
                    'breaks' => [],
                    'blocks' => [[
                        'start' => null,
                        'end' => null,
                        'label' => ucfirst(str_replace('_', ' ', $exception->type)),
                        'type' => $exception->type,
                        'reason' => $exception->reason,
                        'all_day' => true,
                    ]],
                    'available_windows' => [],
                    'available_slots' => [],
                    'is_working_day' => false,
                ];
            }
        }

        $schedules = PhysicianSchedule::query()
            ->where('physician_id', $physician->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->orderBy('start_time')
            ->get();

        $workBlocks = [];
        $breaks = [];

        foreach ($schedules->where('is_break', false) as $block) {
            $workBlocks[] = [
                'start' => substr((string) $block->start_time, 0, 8),
                'end' => substr((string) $block->end_time, 0, 8),
                'label' => $this->formatTimeRange(
                    substr((string) $block->start_time, 0, 8),
                    substr((string) $block->end_time, 0, 8)
                ),
            ];
        }

        foreach ($schedules->where('is_break', true) as $break) {
            $breaks[] = [
                'start' => substr((string) $break->start_time, 0, 8),
                'end' => substr((string) $break->end_time, 0, 8),
                'label' => $this->formatTimeRange(
                    substr((string) $break->start_time, 0, 8),
                    substr((string) $break->end_time, 0, 8)
                ),
                'reason' => 'Break',
            ];
        }

        if ($workBlocks === [] && $exceptions->where('type', 'special_schedule')->isNotEmpty()) {
            foreach ($exceptions->where('type', 'special_schedule') as $special) {
                $workBlocks[] = [
                    'start' => substr((string) $special->start_time, 0, 8),
                    'end' => substr((string) $special->end_time, 0, 8),
                    'label' => $this->formatTimeRange(
                        substr((string) $special->start_time, 0, 8),
                        substr((string) $special->end_time, 0, 8)
                    ),
                ];
            }
        }

        $blocks = [];
        foreach ($exceptions->whereIn('type', ['unavailable', 'blocked', 'leave', 'holiday', 'meeting']) as $exception) {
            $allDay = $exception->start_time === null || $exception->end_time === null;
            $start = $allDay ? null : substr((string) $exception->start_time, 0, 8);
            $end = $allDay ? null : substr((string) $exception->end_time, 0, 8);
            $blocks[] = [
                'start' => $start,
                'end' => $end,
                'label' => $allDay
                    ? ucfirst(str_replace('_', ' ', $exception->type))
                    : $this->formatTimeRange($start, $end),
                'type' => $exception->type,
                'reason' => $exception->reason,
                'all_day' => $allDay,
            ];
        }

        $availableWindows = $this->getConsultationWindows($physician, $date)
            ->map(fn (array $w) => [
                'start' => $w['start'],
                'end' => $w['end'],
                'label' => $this->formatTimeRange($w['start'], $w['end']),
            ])
            ->values()
            ->all();

        return [
            'work_blocks' => $workBlocks,
            'breaks' => $breaks,
            'blocks' => $blocks,
            'available_windows' => $availableWindows,
            'available_slots' => $this->getAvailableSlots($physician, $date),
            'is_working_day' => $availableWindows !== [],
        ];
    }

    /**
     * @return Collection<int, array{start: string, end: string}>
     */
    public function getConsultationWindows(Physician $physician, Carbon $date): Collection
    {
        $exceptions = PhysicianScheduleException::query()
            ->where('physician_id', $physician->id)
            ->whereDate('date', $date->format('Y-m-d'))
            ->get();

        foreach ($exceptions as $exception) {
            if (in_array($exception->type, ['unavailable', 'blocked', 'leave', 'holiday', 'meeting'], true)) {
                if ($exception->start_time === null || $exception->end_time === null) {
                    return collect();
                }
            }
        }

        $dayOfWeek = (int) $date->dayOfWeek;
        $schedules = PhysicianSchedule::query()
            ->where('physician_id', $physician->id)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->orderBy('start_time')
            ->get();

        if ($schedules->isEmpty()) {
            $special = $exceptions->where('type', 'special_schedule');
            if ($special->isNotEmpty()) {
                return $special->map(fn ($e) => [
                    'start' => substr((string) $e->start_time, 0, 8),
                    'end' => substr((string) $e->end_time, 0, 8),
                ]);
            }

            return collect();
        }

        $windows = [];
        $consultBlocks = $schedules->where('is_break', false)->values();
        $breaks = $schedules->where('is_break', true)->values();

        foreach ($consultBlocks as $block) {
            $blockStart = substr((string) $block->start_time, 0, 8);
            $blockEnd = substr((string) $block->end_time, 0, 8);

            if ($this->isFullyBlocked($exceptions, $blockStart, $blockEnd)) {
                continue;
            }

            $segments = [['start' => $blockStart, 'end' => $blockEnd]];

            foreach ($breaks as $break) {
                $breakStart = substr((string) $break->start_time, 0, 8);
                $breakEnd = substr((string) $break->end_time, 0, 8);
                $segments = $this->subtractBreak($segments, $breakStart, $breakEnd);
            }

            foreach ($exceptions->whereIn('type', ['unavailable', 'blocked', 'leave', 'holiday', 'meeting']) as $exception) {
                if ($exception->start_time && $exception->end_time) {
                    $segments = $this->subtractBreak(
                        $segments,
                        substr((string) $exception->start_time, 0, 8),
                        substr((string) $exception->end_time, 0, 8)
                    );
                }
            }

            foreach ($segments as $segment) {
                if ($segment['start'] < $segment['end']) {
                    $windows[] = $segment;
                }
            }
        }

        return collect($windows);
    }

    /**
     * @param  list<array{start: string, end: string}>  $segments
     * @return list<array{start: string, end: string}>
     */
    private function subtractBreak(array $segments, string $breakStart, string $breakEnd): array
    {
        $result = [];

        foreach ($segments as $segment) {
            if ($breakEnd <= $segment['start'] || $breakStart >= $segment['end']) {
                $result[] = $segment;

                continue;
            }

            if ($breakStart > $segment['start']) {
                $result[] = ['start' => $segment['start'], 'end' => $breakStart];
            }

            if ($breakEnd < $segment['end']) {
                $result[] = ['start' => $breakEnd, 'end' => $segment['end']];
            }
        }

        return $result;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, PhysicianScheduleException>  $exceptions
     */
    private function isFullyBlocked(Collection $exceptions, string $start, string $end): bool
    {
        foreach ($exceptions as $exception) {
            if (in_array($exception->type, ['unavailable', 'blocked', 'leave', 'holiday'], true)
                && $exception->start_time === null
                && $exception->end_time === null) {
                return true;
            }

            if ($exception->start_time && $exception->end_time
                && substr((string) $exception->start_time, 0, 8) <= $start
                && substr((string) $exception->end_time, 0, 8) >= $end
                && in_array($exception->type, ['unavailable', 'blocked', 'leave', 'holiday', 'meeting'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function getBookedSlots(Physician $physician, Carbon $date): array
    {
        return Appointment::query()
            ->where('physician_id', $physician->id)
            ->whereDate('appointment_date', $date->format('Y-m-d'))
            ->whereIn('status', AppointmentStatus::blockingStatuses())
            ->pluck('start_time')
            ->map(fn ($t) => substr((string) $t, 0, 8))
            ->all();
    }

    private function getDailyAppointmentCount(Physician $physician, Carbon $date): int
    {
        return Appointment::query()
            ->where('physician_id', $physician->id)
            ->whereDate('appointment_date', $date->format('Y-m-d'))
            ->whereIn('status', AppointmentStatus::blockingStatuses())
            ->count();
    }

    /**
     * @param  list<string>  $booked
     */
    private function isSlotBookable(Carbon $date, string $slotStart, array $booked, int $dailyCount, int $maxDaily): bool
    {
        if (in_array($slotStart, $booked, true)) {
            return false;
        }

        if ($dailyCount >= $maxDaily) {
            return false;
        }

        $deadlineHours = (int) $this->settings->get('booking_deadline_hours', 1);
        $slotDateTime = Carbon::parse($date->format('Y-m-d').' '.$slotStart);

        if ($slotDateTime->lte(now()->addHours($deadlineHours))) {
            return false;
        }

        return true;
    }

    private function formatTimeRange(string $start, string $end): string
    {
        return date('g:i A', strtotime($start)).' – '.date('g:i A', strtotime($end));
    }
}
