<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AppSettings extends Settings
{
    public string $company_name;
    public string $company_email;
    public string $company_phone;
    public string $company_address;
    public ?string $logo_path;

    public static function group(): string
    {
        return 'app';
    }
}
