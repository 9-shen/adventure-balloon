# Phase 2 — Settings & Configuration
**Status: ⏳ NEXT**  
**Priority:** 🔴 HIGH — Foundation for all modules  
**Depends On:** Phase 1 ✅  
**Est. Days:** 3–4 _(extended for additional setting groups)_

---

## Goal
Store all business configuration in the **database** (not `.env`).  
Six setting groups: App info, Legal info, PAX Capacity, Bank info, Email (SMTP), WhatsApp (Twilio).

---

## Setting Groups Overview

| Group | Class | Page | Description |
|-------|-------|------|-------------|
| `app` | `AppSettings` | `AppSettingsPage` | Company name, contact, address, logo |
| `legal` | `LegalSettings` | `LegalSettingsPage` | Moroccan legal identifiers (IF, CNSS, etc.) |
| `pax` | `PaxSettings` | `PaxSettingsPage` | Daily PAX capacity + alert threshold |
| `bank` | `BankSettings` | `BankSettingsPage` | Company bank account details |
| `email` | `EmailSettings` | `EmailSettingsPage` | SMTP config + test email |
| `whatsapp` | `WhatsAppSettings` | `WhatsAppSettingsPage` | Twilio config + test message |

---

## Files to Create

### Setting Classes
```
app/Settings/
├── AppSettings.php           ← Company name, email, phone, address, logo
├── LegalSettings.php         ← IF, CNSS, Patente, RC, ICE
├── PaxSettings.php           ← daily_pax_capacity, warning_threshold
├── BankSettings.php          ← bank details for invoices & company profile
├── EmailSettings.php         ← SMTP host, port, credentials, from
└── WhatsAppSettings.php      ← Twilio account_sid, auth_token, from_number
```

### Filament Pages
```
app/Filament/Admin/Pages/Settings/
├── AppSettingsPage.php          ← General info + logo upload
├── LegalSettingsPage.php        ← Legal identifiers
├── PaxSettingsPage.php          ← Capacity limit + warning threshold
├── BankSettingsPage.php         ← Bank account info
├── EmailSettingsPage.php        ← SMTP + "Send Test Email" action
└── WhatsAppSettingsPage.php     ← Twilio + "Send Test WhatsApp" action
```

### Middleware
```
app/Http/Middleware/ApplyEmailSettings.php  ← Override config('mail.*') from DB at runtime
```

### Widget
```
app/Filament/Admin/Widgets/PaxAlertWidget.php  ← Dashboard alert when PAX is near threshold
```

---

## Checklist

### 1 — App Settings (`AppSettings`)
- [ ] Properties: `company_name`, `company_email`, `company_phone`, `company_address`, `logo_path`
- [ ] `AppSettingsPage` with logo upload via Spatie Media Library
- [ ] Logo displayed in Filament sidebar/header

### 2 — Legal Settings (`LegalSettings`)
- [ ] Properties: `identifiant_fiscal`, `cnss_number`, `patente_number`, `registre_commerce`, `ice_number`
- [ ] `LegalSettingsPage` — simple form with all 5 fields
- [ ] These fields appear on generated **PDF invoices** (bottom legal block)

### 3 — PAX Capacity Settings (`PaxSettings`)
- [ ] Properties: `daily_pax_capacity` (int, default 250), `warning_threshold` (int, default 20)
- [ ] `PaxSettingsPage` — two number fields with explanatory help text
- [ ] `PaxAlertWidget` on admin dashboard:
  - Queries today's booked PAX from `bookings` table
  - Calculates `remaining = daily_pax_capacity - booked_pax`
  - Shows **yellow warning** if `remaining <= warning_threshold`
  - Shows **red critical** if `remaining <= 0` (fully booked)
- [ ] `PaxSettings::daily_pax_capacity` replaces the hardcoded `250` in `BookingService` (Phase 7)

### 4 — Bank Settings (`BankSettings`)
- [ ] Properties: `bank_name`, `bank_holder_name`, `bank_account`, `iban`, `swift`, `routing_number`
- [ ] `BankSettingsPage` — form with all 6 fields
- [ ] These fields appear on generated **PDF invoices** (payment details section)

### 5 — Email Settings (`EmailSettings`)
- [ ] Properties: `host`, `port`, `username`, `password`, `encryption`, `from_address`, `from_name`
- [ ] `EmailSettingsPage` + `TestEmailAction`
- [ ] `ApplyEmailSettings` middleware registered in admin panel

### 6 — WhatsApp Settings (`WhatsAppSettings`)
- [ ] Properties: `account_sid`, `auth_token`, `from_number`, `enabled`
- [ ] `WhatsAppSettingsPage` + `TestWhatsAppAction`

### Seeder & Access
- [ ] `SettingsSeeder` — seeds all six groups with sensible defaults
- [ ] Add `SettingsSeeder` to `DatabaseSeeder`
- [ ] All settings pages restricted to `super_admin` only

---

## Key Code Patterns

### AppSettings
```php
// app/Settings/AppSettings.php
class AppSettings extends Settings
{
    public string  $company_name    = '';
    public string  $company_email   = '';
    public string  $company_phone   = '';
    public string  $company_address = '';
    public ?string $logo_path       = null;

    public static function group(): string { return 'app'; }
}
```

### LegalSettings
```php
// app/Settings/LegalSettings.php
class LegalSettings extends Settings
{
    public ?string $identifiant_fiscal  = null;  // IF
    public ?string $cnss_number         = null;  // CNSS
    public ?string $patente_number      = null;  // Patente
    public ?string $registre_commerce   = null;  // RC
    public ?string $ice_number          = null;  // ICE

    public static function group(): string { return 'legal'; }
}
```

### PaxSettings
```php
// app/Settings/PaxSettings.php
class PaxSettings extends Settings
{
    public int $daily_pax_capacity = 250;  // max seats per day
    public int $warning_threshold  = 20;   // alert when remaining <= this

    public static function group(): string { return 'pax'; }
}
```

### BankSettings
```php
// app/Settings/BankSettings.php
class BankSettings extends Settings
{
    public ?string $bank_name        = null;
    public ?string $bank_holder_name = null;
    public ?string $bank_account     = null;
    public ?string $iban             = null;
    public ?string $swift            = null;
    public ?string $routing_number   = null;

    public static function group(): string { return 'bank'; }
}
```

### PaxAlertWidget (Dashboard)
```php
// app/Filament/Admin/Widgets/PaxAlertWidget.php
// Shown on dashboard if remaining PAX <= warning_threshold
// Logic:
$paxSettings  = app(PaxSettings::class);
$bookedToday  = Booking::whereDate('flight_date', today())
                        ->whereIn('booking_status', ['confirmed', 'pending'])
                        ->sum(DB::raw('adult_pax + child_pax'));
$remaining    = $paxSettings->daily_pax_capacity - $bookedToday;

// Widget shows:
// 🟡 Warning  → remaining <= warning_threshold  ("Only {$remaining} PAX remaining today!")
// 🔴 Critical → remaining <= 0                  ("Today is FULLY BOOKED!")
// ✅ Hidden   → remaining > warning_threshold
```

### Runtime Email Override Middleware
```php
// app/Http/Middleware/ApplyEmailSettings.php
public function handle(Request $request, Closure $next)
{
    $settings = app(EmailSettings::class);
    config([
        'mail.mailers.smtp.host'       => $settings->host,
        'mail.mailers.smtp.port'       => $settings->port,
        'mail.mailers.smtp.username'   => $settings->username,
        'mail.mailers.smtp.password'   => $settings->password,
        'mail.mailers.smtp.encryption' => $settings->encryption,
        'mail.from.address'            => $settings->from_address,
        'mail.from.name'               => $settings->from_name,
    ]);
    return $next($request);
}
```

---

## PDF Invoice Usage (Phases 12+)

When a PDF invoice is generated, it will pull from:

| Section | Source Setting |
|---------|---------------|
| Company name & address | `AppSettings` |
| Company logo | `AppSettings::logo_path` |
| Legal block (IF, CNSS, etc.) | `LegalSettings` |
| Payment / Bank details | `BankSettings` |

---

## Notes
- Logo stored via Spatie Media Library (collection `'company-logo'`)
- `PaxSettings::daily_pax_capacity` will be injected into `BookingService` (Phase 7) to replace the hardcoded `250`
- All settings pages grouped under a "Settings" navigation group in Filament sidebar
- Restrict settings navigation group to `super_admin` using `->navigationGroup('Settings')` + `canAccess()`
- `ice_number` (Identifiant Commun de l'Entreprise) is the Moroccan unified tax ID used on all official documents

---

## 🛠️ Lessons Learned: Filament v4
During the implementation of Phase 2, critical Filament v4 architectural changes were discovered and documented:

1. **Custom Pages Forms Migration**
   Filament v4 does not inherently support the `Filament\Forms\Form` injection structure or legacy views (`x-filament-panels::form.actions`) for custom pages decoupled from Resources.
   
2. **Schema Method Typing**
   The trait `InteractsWithForms` dictates using `Schema` instead of `Form`.
   ```php
   use Filament\Schemas\Schema;
   
   public function form(Schema $form): Schema
   {
       return $form->schema([ ... ]);
   }
   ```

3. **Layout UI NameSpacing**
   UI structure tools previously nested under Forms are now globally located under Schemas:
   - `use Filament\Schemas\Components\Section;`
   - `use Filament\Schemas\Components\Grid;`
   - Inputs still sit underneath `use Filament\Forms\Components\TextInput;`

4. **Blade Wrapper Update**
   Custom decoupled pages require the Blade view to manually wrap the Native form and submit buttons instead of relying on legacy pre-compiled renderers:
   ```html
   <x-filament-panels::page>
       <form wire:submit="save">
           {{ $this->form }}
           <div class="mt-6">
               <x-filament::button type="submit">Save Settings</x-filament::button>
           </div>
       </form>
   </x-filament-panels::page>
   ```
