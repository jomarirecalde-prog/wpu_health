<?php

namespace App\Services\Appointments;

use App\Models\Appointment;
use App\Models\Physician;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class CalendarFeedService
{
    public function __construct(
        private readonly SlotGenerationService $slotService,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function buildFeed(Carbon $start, Carbon $end, array $filters, string $role): array
    {
        $events = $this->appointmentEvents($start, $end, $filters);

        $physicianIds = $this->resolveOverlayPhysicianIds($filters, $role);
        foreach ($physicianIds as $physicianId) {
            $physician = Physician::query()->find($physicianId);
            if ($physician === null || ! $physician->isActive()) {
                continue;
            }

            $events = array_merge($events, $this->scheduleOverlayEvents($physician, $start, $end));
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getDateDetail(Carbon $date, array $filters, string $role): array
    {
        $physicianId = $this->resolveDetailPhysicianId($filters, $role);

        $appointmentQuery = Appointment::query()
            ->with(['physician:id,name,professional_title', 'portalUser:id,name'])
            ->whereDate('appointment_date', $date->format('Y-m-d'))
            ->orderBy('start_time');

        if ($physicianId) {
            $appointmentQuery->where('physician_id', $physicianId);
        }

        if (! empty($filters['portal_user_id'])) {
            $appointmentQuery->where('portal_user_id', (int) $filters['portal_user_id']);
        }

        if (! empty($filters['department_id'])) {
            $appointmentQuery->whereHas('physician', fn ($q) => $q->where('department_id', (int) $filters['department_id']));
        }

        if (! empty($filters['status'])) {
            $appointmentQuery->where('status', $filters['status']);
        }

        if (! empty($filters['consultation_type'])) {
            $appointmentQuery->where('consultation_type', $filters['consultation_type']);
        }

        $appointments = $appointmentQuery->get()->map(fn (Appointment $apt) => [
            'id' => $apt->id,
            'patient_name' => $apt->portalUser->name ?? 'Patient',
            'physician_name' => $apt->physician->displayName() ?? '',
            'time' => $this->formatTime((string) $apt->start_time),
            'time_range' => $apt->formattedTimeRange(),
            'status' => $apt->status->value,
            'status_label' => $apt->statusLabel(),
            'consultation_type' => config('appointments.consultation_types')[$apt->consultation_type] ?? $apt->consultation_type,
        ])->values()->all();

        $schedule = null;
        if ($physicianId) {
            $physician = Physician::query()->findOrFail($physicianId);
            $meta = $this->slotService->getScheduleMetadata($physician, $date);
            $schedule = [
                'physician_id' => $physician->id,
                'physician_name' => $physician->displayName(),
                'work_blocks' => $meta['work_blocks'],
                'breaks' => $meta['breaks'],
                'blocks' => $meta['blocks'],
                'available_windows' => $meta['available_windows'],
                'available_slots' => $meta['available_slots'],
                'is_working_day' => $meta['is_working_day'],
            ];
        }

        return [
            'date' => $date->format('Y-m-d'),
            'date_label' => $date->format('F j, Y'),
            'schedule' => $schedule,
            'appointments' => $appointments,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    private function appointmentEvents(Carbon $start, Carbon $end, array $filters): array
    {
        $query = Appointment::query()
            ->with(['physician:id,name,professional_title,department_id', 'portalUser:id,name'])
            ->whereBetween('appointment_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);

        if (! empty($filters['physician_id'])) {
            $query->where('physician_id', (int) $filters['physician_id']);
        }

        if (! empty($filters['department_id'])) {
            $query->whereHas('physician', fn ($q) => $q->where('department_id', (int) $filters['department_id']));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['consultation_type'])) {
            $query->where('consultation_type', $filters['consultation_type']);
        }

        if (! empty($filters['portal_user_id'])) {
            $query->where('portal_user_id', (int) $filters['portal_user_id']);
        }

        return $query->orderBy('appointment_date')->orderBy('start_time')->get()
            ->map(fn (Appointment $apt) => $this->formatAppointmentEvent($apt))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function scheduleOverlayEvents(Physician $physician, Carbon $start, Carbon $end): array
    {
        $events = [];
        $period = CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay());

        foreach ($period as $day) {
            $dateStr = $day->format('Y-m-d');
            $meta = $this->slotService->getScheduleMetadata($physician, $day);

            foreach ($meta['blocks'] as $i => $block) {
                if ($block['all_day']) {
                    $events[] = [
                        'id' => "block-{$physician->id}-{$dateStr}-{$i}",
                        'start' => $dateStr,
                        'allDay' => true,
                        'display' => 'background',
                        'classNames' => ['wpu-cal-bg-blocked'],
                        'extendedProps' => [
                            'kind' => 'blocked',
                            'label' => $block['label'],
                            'reason' => $block['reason'],
                        ],
                    ];

                    continue;
                }

                $events[] = [
                    'id' => "block-{$physician->id}-{$dateStr}-{$i}",
                    'start' => "{$dateStr}T{$block['start']}",
                    'end' => "{$dateStr}T{$block['end']}",
                    'display' => 'background',
                    'classNames' => ['wpu-cal-bg-blocked'],
                    'extendedProps' => [
                        'kind' => 'blocked',
                        'label' => $block['label'],
                        'reason' => $block['reason'],
                    ],
                ];
            }

            foreach ($meta['breaks'] as $i => $break) {
                $events[] = [
                    'id' => "break-{$physician->id}-{$dateStr}-{$i}",
                    'start' => "{$dateStr}T{$break['start']}",
                    'end' => "{$dateStr}T{$break['end']}",
                    'display' => 'background',
                    'classNames' => ['wpu-cal-bg-break'],
                    'extendedProps' => [
                        'kind' => 'break',
                        'label' => $break['label'],
                        'reason' => $break['reason'],
                    ],
                ];
            }

            foreach ($meta['available_windows'] as $i => $window) {
                $events[] = [
                    'id' => "avail-{$physician->id}-{$dateStr}-{$i}",
                    'start' => "{$dateStr}T{$window['start']}",
                    'end' => "{$dateStr}T{$window['end']}",
                    'display' => 'background',
                    'classNames' => ['wpu-cal-bg-available'],
                    'extendedProps' => ['kind' => 'available'],
                ];
            }

            foreach ($meta['available_slots'] as $i => $slot) {
                $events[] = [
                    'id' => "slot-{$physician->id}-{$dateStr}-{$i}",
                    'title' => 'Available',
                    'start' => "{$dateStr}T{$slot['start']}",
                    'end' => "{$dateStr}T{$slot['end']}",
                    'classNames' => ['wpu-cal-slot-available'],
                    'extendedProps' => [
                        'kind' => 'slot_available',
                        'slot_start' => $slot['start'],
                        'physician_id' => $physician->id,
                    ],
                ];
            }

            if (! $meta['is_working_day'] && $meta['blocks'] === [] && $meta['work_blocks'] === []) {
                $events[] = [
                    'id' => "off-{$physician->id}-{$dateStr}",
                    'start' => $dateStr,
                    'allDay' => true,
                    'display' => 'background',
                    'classNames' => ['wpu-cal-bg-unavailable'],
                    'extendedProps' => ['kind' => 'unavailable'],
                ];
            }
        }

        return $events;
    }

    private function formatAppointmentEvent(Appointment $apt): array
    {
        $date = $apt->appointment_date->format('Y-m-d');
        $startTime = substr((string) $apt->start_time, 0, 8);
        $endTime = substr((string) $apt->end_time, 0, 8);
        $patientName = $apt->portalUser->name ?? 'Patient';
        $physicianName = $apt->physician->displayName() ?? 'Physician';

        return [
            'id' => $apt->id,
            'title' => $patientName,
            'start' => "{$date}T{$startTime}",
            'end' => "{$date}T{$endTime}",
            'backgroundColor' => $apt->statusColor(),
            'borderColor' => $apt->statusColor(),
            'classNames' => ['wpu-cal-apt', 'wpu-cal-apt--'.$apt->status->value],
            'extendedProps' => [
                'kind' => 'appointment',
                'appointment_id' => $apt->id,
                'appointment_number' => $apt->appointment_number,
                'patient_name' => $patientName,
                'physician_name' => $physicianName,
                'physician_id' => $apt->physician_id,
                'time_label' => $this->formatTime($startTime),
                'time_range' => $apt->formattedTimeRange(),
                'consultation_type' => config('appointments.consultation_types')[$apt->consultation_type] ?? $apt->consultation_type,
                'consultation_type_key' => $apt->consultation_type,
                'status' => $apt->status->value,
                'status_label' => $apt->statusLabel(),
                'status_icon' => $apt->status->icon(),
                'reason' => $apt->reason,
                'date_label' => $apt->appointment_date->format('F j, Y'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return list<int>
     */
    private function resolveOverlayPhysicianIds(array $filters, string $role): array
    {
        if ($role === 'physician' && ! empty($filters['physician_id'])) {
            return [(int) $filters['physician_id']];
        }

        if (! empty($filters['physician_id'])) {
            return [(int) $filters['physician_id']];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function resolveDetailPhysicianId(array $filters, string $role): ?int
    {
        if ($role === 'physician' && ! empty($filters['physician_id'])) {
            return (int) $filters['physician_id'];
        }

        if (! empty($filters['physician_id'])) {
            return (int) $filters['physician_id'];
        }

        return null;
    }

    private function formatTime(string $time): string
    {
        return date('g:i A', strtotime($time));
    }
}
