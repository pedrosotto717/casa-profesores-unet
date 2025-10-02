<?php declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Reservation Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration options for the reservation system.
    | These settings control time windows, duration limits, and other
    | reservation-related parameters.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Time Windows
    |--------------------------------------------------------------------------
    |
    | These settings control the time windows for making reservations.
    |
    */

    // Minimum hours in advance to make a reservation
    'min_advance_hours' => env('RESERVATION_MIN_ADVANCE_HOURS', 2),

    // Maximum days in advance to make a reservation
    'max_advance_days' => env('RESERVATION_MAX_ADVANCE_DAYS', 30),

    // Maximum duration of a reservation in hours
    'max_duration_hours' => env('RESERVATION_MAX_DURATION_HOURS', 8),

    /*
    |--------------------------------------------------------------------------
    | Cancellation Rules
    |--------------------------------------------------------------------------
    |
    | These settings control when users can cancel their reservations.
    |
    */

    // Hours before start time when user can no longer cancel (admin can always cancel)
    'cancel_before_hours' => env('RESERVATION_CANCEL_BEFORE_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Availability Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the availability calculation.
    |
    */

    // Default slot size in minutes for availability endpoint
    'default_slot_minutes' => env('RESERVATION_DEFAULT_SLOT_MINUTES', 60),

    // Minimum slot size in minutes
    'min_slot_minutes' => env('RESERVATION_MIN_SLOT_MINUTES', 15),

    // Maximum slot size in minutes
    'max_slot_minutes' => env('RESERVATION_MAX_SLOT_MINUTES', 480), // 8 hours
];

