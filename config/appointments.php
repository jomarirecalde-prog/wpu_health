<?php

return [
    'cancellation_hours_before' => (int) env('APPOINTMENT_CANCELLATION_HOURS', 2),
    'booking_deadline_hours' => (int) env('APPOINTMENT_BOOKING_DEADLINE_HOURS', 1),
    'reminder_hours' => array_map('intval', explode(',', env('APPOINTMENT_REMINDER_HOURS', '24,1'))),
    'default_consultation_duration' => 30,
    'appointment_number_prefix' => 'WPU',
    'consultation_types' => [
        'general' => 'General Consultation',
        'follow_up' => 'Follow-up',
        'dental' => 'Dental Consultation',
        'health' => 'Health Consultation',
        'medical_certificate' => 'Medical Certificate',
    ],
];
