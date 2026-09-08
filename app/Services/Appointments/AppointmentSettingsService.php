<?php

namespace App\Services\Appointments;

use App\Models\AppointmentSetting;
use Illuminate\Support\Facades\Cache;

class AppointmentSettingsService
{
    private const CACHE_KEY = 'appointment:settings';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, 300, function () {
            $defaults = config('appointments');
            $stored = AppointmentSetting::query()->pluck('setting_value', 'setting_key')->all();

            return array_merge($defaults, $this->castStored($stored));
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $serialized = is_array($value) ? json_encode($value) : (string) $value;

        AppointmentSetting::query()->updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $serialized]
        );

        Cache::forget(self::CACHE_KEY);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @param  array<string, string>  $stored
     * @return array<string, mixed>
     */
    private function castStored(array $stored): array
    {
        $casted = [];

        foreach ($stored as $key => $value) {
            $decoded = json_decode($value, true);
            $casted[$key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        if (isset($casted['reminder_hours']) && is_string($casted['reminder_hours'])) {
            $casted['reminder_hours'] = array_map('intval', explode(',', $casted['reminder_hours']));
        }

        foreach (['cancellation_hours_before', 'booking_deadline_hours', 'default_consultation_duration'] as $intKey) {
            if (isset($casted[$intKey])) {
                $casted[$intKey] = (int) $casted[$intKey];
            }
        }

        return $casted;
    }
}
