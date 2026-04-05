<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WhatsAppSettings extends Settings
{
    public ?string $account_sid;
    public ?string $auth_token;
    public ?string $from_number;
    public bool $enabled;

    public static function group(): string
    {
        return 'whatsapp';
    }
}
