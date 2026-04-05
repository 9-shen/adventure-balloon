<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PaxSettings extends Settings
{
    public int $daily_pax_capacity; // Max PAX per day (global cap)
    public int $warning_threshold;  // Show alert on dashboard when remaining <= this

    public static function group(): string
    {
        return 'pax';
    }
}
