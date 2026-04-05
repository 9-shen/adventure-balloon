<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class BankSettings extends Settings
{
    public ?string $bank_name;
    public ?string $bank_holder_name;
    public ?string $bank_account;
    public ?string $iban;
    public ?string $swift;
    public ?string $routing_number;

    public static function group(): string
    {
        return 'bank';
    }
}
