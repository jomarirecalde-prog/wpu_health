<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Rescheduled = 'rescheduled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Confirmed => 'Confirmed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
            self::Rescheduled => 'Rescheduled',
            self::Completed => 'Completed',
            self::NoShow => 'No-show',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#f59e0b',
            self::Confirmed => '#2563eb',
            self::Rejected => '#dc2626',
            self::Cancelled => '#6b7280',
            self::Rescheduled => '#8b5cf6',
            self::Completed => '#059669',
            self::NoShow => '#991b1b',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::Confirmed => 'check-circle',
            self::Rejected => 'times-circle',
            self::Cancelled => 'ban',
            self::Rescheduled => 'exchange-alt',
            self::Completed => 'check-double',
            self::NoShow => 'user-slash',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Rejected, self::Cancelled],
            self::Confirmed => [self::Completed, self::Cancelled, self::NoShow, self::Rescheduled],
            self::Rescheduled => [self::Pending, self::Confirmed, self::Cancelled],
            default => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Statuses that block a time slot from being booked.
     *
     * @return list<string>
     */
    public static function blockingStatuses(): array
    {
        return [
            self::Pending->value,
            self::Confirmed->value,
            self::Rescheduled->value,
        ];
    }
}
