<?php

namespace Database\Seeders;

use App\Settings\AppSettings;
use App\Settings\BankSettings;
use App\Settings\EmailSettings;
use App\Settings\LegalSettings;
use App\Settings\PaxSettings;
use App\Settings\WhatsAppSettings;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // ── App Settings ─────────────────────────────────────────────────────────
        $app = app(AppSettings::class);
        $app->company_name    = 'Booklix';
        $app->company_email   = 'info@booklix.com';
        $app->company_phone   = '+212 600 000 000';
        $app->company_address = 'Marrakech, Morocco';
        $app->logo_path       = null;
        $app->save();

        // ── Legal Settings ────────────────────────────────────────────────────────
        $legal = app(LegalSettings::class);
        $legal->identifiant_fiscal = null;
        $legal->cnss_number        = null;
        $legal->patente_number     = null;
        $legal->registre_commerce  = null;
        $legal->ice_number         = null;
        $legal->save();

        // ── PAX Settings ──────────────────────────────────────────────────────────
        $pax = app(PaxSettings::class);
        $pax->daily_pax_capacity = 250;
        $pax->warning_threshold  = 20;
        $pax->save();

        // ── Bank Settings ─────────────────────────────────────────────────────────
        $bank = app(BankSettings::class);
        $bank->bank_name        = null;
        $bank->bank_holder_name = null;
        $bank->bank_account     = null;
        $bank->iban             = null;
        $bank->swift            = null;
        $bank->routing_number   = null;
        $bank->save();

        // ── Email Settings ────────────────────────────────────────────────────────
        $email = app(EmailSettings::class);
        $email->host         = 'smtp.mailtrap.io';
        $email->port         = 587;
        $email->username     = null;
        $email->password     = null;
        $email->encryption   = 'tls';
        $email->from_address = 'noreply@booklix.com';
        $email->from_name    = 'Booklix';
        $email->save();

        // ── WhatsApp Settings ─────────────────────────────────────────────────────
        $wa = app(WhatsAppSettings::class);
        $wa->account_sid = null;
        $wa->auth_token  = null;
        $wa->from_number = null;
        $wa->enabled     = false;
        $wa->save();

        $this->command->info('✅ All settings seeded with defaults.');
    }
}
