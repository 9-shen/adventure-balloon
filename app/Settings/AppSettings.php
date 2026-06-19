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
    public string $currency;

    public function getIsoCurrency(): string
    {
        return match ($this->currency ?? 'MAD') {
            'US' => 'USD',
            'Tunisia Dinar' => 'TND',
            default => $this->currency ?? 'MAD',
        };
    }

    public static function group(): string
    {
        return 'app';
    }
}
