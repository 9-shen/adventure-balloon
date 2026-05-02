# 🪂 Adventure Balloon — Developer Workflow Guide

> **Stack:** Laravel 12 · Filament 4 · MySQL 8 · Spatie Suite  
> **Last Updated:** May 2026  
> **Repo:** [9-shen/adventure-balloon](https://github.com/9-shen/adventure-balloon)

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Local Development Setup](#2-local-development-setup)
3. [Architecture at a Glance](#3-architecture-at-a-glance)
4. [Portal Map — Who Goes Where](#4-portal-map--who-goes-where)
5. [Business Workflows](#5-business-workflows)
   - [Booking Lifecycle (Regular)](#51-booking-lifecycle-regular)
   - [Booking Lifecycle (Partner)](#52-booking-lifecycle-partner)
   - [Dispatch Workflow](#53-dispatch-workflow)
   - [Invoice Workflow](#54-invoice-workflow)
   - [Payment Workflow](#55-payment-workflow)
6. [Code Conventions & Patterns](#6-code-conventions--patterns)
7. [Adding a New Feature — Step-by-Step](#7-adding-a-new-feature--step-by-step)
8. [Adding a New Role / Portal](#8-adding-a-new-role--portal)
9. [Notification System](#9-notification-system)
10. [Settings System](#10-settings-system)
11. [Common Pitfalls (Filament v4)](#11-common-pitfalls-filament-v4)
12. [Git & Deployment Workflow](#12-git--deployment-workflow)
13. [Current Phase Status](#13-current-phase-status)

---

## 1. Project Overview

**Adventure Balloon** is a full-featured business operations platform for a Hot Air Balloon company. It manages:

| Domain | Description |
|--------|-------------|
| **CRM** | Partner accounts, KYC data, customer records |
| **Bookings** | Regular (admin-created) and Partner (self-service) booking streams |
| **Dispatch** | Driver/vehicle assignment with email + WhatsApp notifications |
| **Finance** | Payment tracking, invoicing (PDF), transport billing |
| **Operations** | Greeter attendance, accountant payment verification, guide assignments |

### Core Principles

- All business config stored in the **database** (not `.env`) via Spatie Settings
- **No payment gateway** — tracking only (Cash / Wire / Online)
- **250 PAX/day** global capacity limit (`PaxSettings::daily_pax_capacity`)
- Two booking streams: `type='regular'` and `type='partner'`
- Every role has its own scoped portal dashboard

---

## 2. Local Development Setup

### Prerequisites

```bash
# Required
PHP 8.2+   MySQL 8+   Composer   Node.js 18+
XAMPP (or equivalent local MySQL server)
```

### First-Time Setup

```bash
# 1. Clone
git clone https://github.com/9-shen/adventure-balloon.git
cd adventure-balloon

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies & build assets
npm install
npm run build

# 4. Configure environment
cp .env.example .env
php artisan key:generate
```

### `.env` Minimum Config

```ini
APP_URL=http://127.0.0.1:8000      # Must match actual serve URL for media
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booklix
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database           # Use Redis in production

FILESYSTEM_DISK=public              # CRITICAL: media needs public HTTP access
MEDIA_DISK=public
```

### Database Setup

```bash
# Run all migrations
php artisan migrate

# Seed roles, permissions & default admin
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=AdminUserSeeder
php artisan db:seed --class=SettingsSeeder

# OR run all seeders at once
php artisan db:seed

# Create storage symlink (required for avatars/media)
php artisan storage:link

# Discover Spatie settings classes
php artisan settings:discover
```

### Running the App

```bash
# Terminal 1 — HTTP server
php artisan serve

# Terminal 2 — Queue worker (required for email/WhatsApp notifications)
php artisan queue:work --queue=notifications,default

# Terminal 3 — (optional) Vite hot-reload during theme development
npm run dev
```

**Access Points:**

| URL | Panel | Role |
|-----|-------|------|
| `http://127.0.0.1:8000/admin` | Admin Panel | super_admin, admin |
| `http://127.0.0.1:8000/accountant` | Finance Portal | accountant |
| `http://127.0.0.1:8000/manager` | Manager Panel | manager |
| `http://127.0.0.1:8000/partner` | Partner Panel | partner |
| `http://127.0.0.1:8000/transport` | Transport Panel | transport |
| `http://127.0.0.1:8000/driver` | Driver Panel | driver |
| `http://127.0.0.1:8000/greeter` | Greeter Panel | greeter |
| `http://127.0.0.1:8000/guide` | Guide Panel | guide |
| `http://127.0.0.1:8000/dispatcher` | Dispatcher Panel | dispatcher |

---

## 3. Architecture at a Glance

### Directory Structure

```
app/
├── Filament/
│   ├── Admin/              → /admin panel (super_admin, admin)
│   ├── Accountant/         → /accountant panel "Finance Portal" (accountant role)
│   │   ├── Pages/          →   AccountantDashboard, BookingCalendarPage
│   │   ├── Resources/      →   AccountantBookingResource, InvoiceResource,
│   │   │                       PartnerInvoiceResource, TransportBillResource,
│   │   │                       TransporterBillingResource, FinanceReportResource
│   │   └── Widgets/        →   CashFlowOverview, RecentInvoicesWidget
│   ├── Manager/            → /manager panel (manager role)
│   ├── Partner/            → /partner panel (partner role)
│   ├── Transport/          → /transport panel (transport role)
│   ├── Driver/             → /driver panel (driver role)
│   ├── Greeter/            → /greeter panel (greeter role)
│   ├── Guide/              → /guide panel (guide role)
│   └── Dispatcher/         → /dispatcher panel (dispatcher role)
├── Models/                 → Eloquent models
├── Services/               → BookingService, DispatchService, InvoiceService
├── Notifications/          → Email & WhatsApp notification classes
├── Settings/               → Spatie Settings classes (DB-stored config)
├── Exports/                → Maatwebsite Excel export classes
└── Providers/
    └── Filament/           → Panel provider registrations (one file per panel)
resources/views/pdf/        → DomPDF invoice & transport bill templates
```

### Resource Folder Pattern (modular)

All Filament resources follow this structure:

```
Resources/Bookings/
├── BookingResource.php         → Resource class (navigation, access control)
├── Pages/
│   ├── ListBookings.php
│   ├── CreateBooking.php
│   ├── EditBooking.php
│   └── ViewBooking.php
├── Schemas/
│   ├── BookingWizard.php       → 5-step create wizard
│   └── BookingEditForm.php     → flat edit form sections
├── Tables/
│   └── BookingsTable.php       → columns, filters, actions
└── RelationManagers/
    └── BookingCustomersRelationManager.php
```

### Service Layer

Business logic lives in services, not in Filament page classes:

```
app/Services/
├── BookingService.php      → createBooking(), checkAvailability(), calculatePricing()
├── DispatchService.php     → createDispatch(), notifyTransporter(), sendWhatsAppToDrivers()
├── InvoiceService.php      → generate(), generatePdf(), markSent(), markPaid()
└── TransportBillService.php
```

---

## 4. Portal Map — Who Goes Where

```
User Role         Panel URL         Branding / Color    Capabilities
──────────────────────────────────────────────────────────────────────────────────
super_admin   →   /admin            (Default Red)       Full access + Settings
admin         →   /admin            (Default Red)       Full access (no Settings)
manager       →   /manager          Amber #f59e0b       Bookings, Dispatch (full CRUD);
                                                        Partners, Products, Transport (read-only)
accountant    →   /accountant       Blue · "Finance Portal"  Finance bookings, invoicing,
                                                        transport billing, financial reports
greeter       →   /greeter          (Green)             Today's bookings, PAX attendance
partner       →   /partner          Teal #0e7490        Own bookings (create/view), own invoices
transport     →   /transport        Orange/Amber        Own fleet, own dispatches, own bills
driver        →   /driver           (Mobile-first)      Assigned dispatches only
guide         →   /guide            Teal                Assigned bookings view
dispatcher    →   /dispatcher       (Custom)            Assigned partner bookings + dispatch (read-only)
```

### `canAccessPanel()` Logic

Each role is restricted via `User::canAccessPanel()` in `app/Models/User.php`. Key rule: **each role goes to exactly one panel**.

```php
// Pattern: guard inactive users first, then check panel→role mapping
public function canAccessPanel(Panel $panel): bool
{
    if (!$this->is_active) return false;
    return match ($panel->getId()) {
        'partner'    => $this->hasRole('partner') && $this->partner_id !== null,
        'manager'    => $this->hasRole('manager'),
        'accountant' => $this->hasRole('accountant'),   // → /accountant (Finance Portal)
        'transport'  => $this->hasRole('transport'),
        'driver'     => $this->hasRole('driver'),
        'greeter'    => $this->hasRole('greeter'),
        'guide'      => $this->hasRole('guide'),
        'dispatcher' => $this->hasRole('dispatcher'),
        'admin'      => $this->hasAnyRole(['super_admin', 'admin']),  // accountant excluded
        default      => false,
    };
}
```

---

## 5. Business Workflows

### 5.1 Booking Lifecycle (Regular)

```
Admin/Manager creates booking (5-step wizard)
    │
    ├── Step 1: Flight Details (product, date, PAX count — checks 250-cap)
    ├── Step 2: Customer Details (repeater — one entry per PAX)
    ├── Step 3: Pricing & Discounts (auto-calculated from product prices)
    ├── Step 4: Payment (method, status, amount paid)
    └── Step 5: Review & Confirm → DB insert (transaction)
                │
                ▼
         Status: PENDING  (payment_status: due)
                │
                ▼ Admin/Manager clicks "Confirm Booking"
         Status: CONFIRMED  (confirmed_by, confirmed_at set)
                │            BookingConfirmedNotification → partner email (if partner booking)
                ▼
         Dispatch created → Transport notified (email + WhatsApp)
                │
                ▼ On the day: Greeter marks attendance
         attendance: show | no_show  (per PAX)
                │
                ▼ Accountant verifies payment
         payment_status: paid | partial
                │
                ▼
         Status: COMPLETED
```

**Booking Reference Format:**
- Regular: `BLX-2026-0001`
- Partner: `PBX-2026-0001`
- Sequence resets annually (per year, per prefix)

**PAX Capacity Check:**
```php
// BookingService::getAvailablePax(Carbon $date): int
// Counts ALL bookings (regular + partner) with status pending|confirmed
$available = PaxSettings::daily_pax_capacity - $usedToday;
// Blocks creation if $newPax > $available
```

---

### 5.2 Booking Lifecycle (Partner)

```
Partner logs into /partner portal
    │
    ├── Sees only products assigned to them (via partner_products pivot)
    ├── 3-step wizard (Flight → Passengers → Review)
    ├── Pricing auto-loaded from partner_products.partner_adult_price
    └── On submit → PartnerBookingNotification → admin email
                │
                ▼
    Admin/Manager sees new booking in /admin or /manager
                │
                ▼ Admin confirms → BookingConfirmedNotification → partner email
                │
    Monthly invoicing cycle:
    Partner Portal → Account Statement → View Invoices
    Admin → Invoicing → Select partner → basket bookings → Create Invoice → PDF sent
```

**Partner Price Priority:**
```
1. partner_products.partner_adult_price  (if pivot row exists and is active)
2. products.base_adult_price             (fallback)
```

---

### 5.3 Dispatch Workflow

```
Admin confirms booking
    │
    ▼ Admin/Manager creates dispatch (linked to booking)
    │   → Selects transport company
    │   → Assigns drivers (manual or auto-suggest via PAX ÷ vehicle_capacity)
    │   → Sets pickup time, pickup location, drop-off location
    │
    ▼ On save: DispatchAssignedNotification → transport company email (auto)
               (Rich HTML: manifest, passenger list, driver-vehicle assignments)
    │
    ▼ Optional: "Send WhatsApp to Drivers" button on ViewDispatch
               → Twilio API → per-driver WhatsApp message
               (date, time, pickup, PAX count, dispatch ref)
    │
    ▼ Status tracking:
    pending → confirmed → in_progress → delivered | cancelled

    Dispatch Reference Format: DSP-2026-0001
```

**Driver Assignment Algorithm:**
```
drivers_needed = ceil(total_pax / vehicle_capacity)
First driver gets: min(remaining_pax, vehicle_capacity)
Last driver gets: remainder
```

---

### 5.4 Invoice Workflow

```
Admin → Invoicing → PartnerInvoiceResource
    │
    ▼ Select partner → "View Bookings" → basket selection
    │   (only uninvoiced bookings shown; "Not Yet Invoiced" filter)
    │
    ▼ Click "Create Invoice (N bookings)"
    │   → Enter tax_rate + notes
    │   → InvoiceService::generate() creates Invoice + InvoiceItems
    │   → Stamps invoiced_at on each booking (prevents double-invoicing)
    │   → InvoiceIssuedNotification → partner email (PDF attached)
    │
    ▼ Invoice statuses:
    draft → sent (markSent) → paid (markPaid, requires payment_reference)
              ↑ re-sends PDF email        ↑ records paid_at

    Invoice Reference Format: INV-2026-0001  (resets Jan 1 each year)
    Payment Terms: partner.payment_terms_days (default: 30 days)
```

---

### 5.5 Payment Workflow

```
Payment Methods: cash | wire | online   (recorded manually — no gateway)

Payment Statuses:
    due      → nothing received
    partial  → deposit paid, balance outstanding
    paid     → full amount received
    on_site  → customer will pay at location

Accountant Process Payment action:
    → Slide-over form: payment_method, amount_paid, payment_status
    → balance_due = final_amount - amount_paid (auto-calculated)
    → If amount_paid >= final_amount → booking_status auto-set to completed
```

---

## 6. Code Conventions & Patterns

### Settings Access (DB-stored config)

```php
// CORRECT: resolve from container
$settings = app(AppSettings::class);
$companyName = $settings->company_name;      // NOT ->name
$companyEmail = $settings->company_email;    // NOT ->email ⚠️

// In service layer
use App\Settings\EmailSettings;
$email = app(EmailSettings::class);
```

### Email Configuration (3-layer approach)

All three layers must be present for DB-driven SMTP to work in every context:

```php
// Layer 1: HTTP requests → ApplyEmailSettings middleware (registered in each PanelProvider)
// Layer 2: Queued jobs → Queue::before() hook in AppServiceProvider
// Layer 3: supervisord.conf → queue worker listens to both 'notifications,default' queues
```

### Notifications Pattern

```php
// Always use try/catch, always log — never crash the request
try {
    Notification::route('mail', $email)->notify(new MyNotification($data));
} catch (\Exception $e) {
    Log::error('MyNotification failed: ' . $e->getMessage());
}

// For queued notifications
class MyNotification extends Notification implements ShouldQueue {
    public string $queue = 'notifications';
}
```

### Reactive Forms (Filament v4)

```php
// CORRECT import for reactive form closures
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

// NOT: use Filament\Forms\Get;  ← Does not exist in v4
```

### Actions Namespace (Filament v4)

```php
// ALL actions from Filament\Actions — not Filament\Tables\Actions
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkAction;
// Filament\Tables\Actions\* does NOT exist in v4
```

### Displaying Relationship Data on Edit Forms

```php
// WRONG — TextInput::default() only fires on create
TextInput::make('partner_name')->default(fn($record) => $record->partner->company_name)

// CORRECT — Placeholder re-evaluates against live $record
Placeholder::make('partner_name')
    ->content(fn($record) => $record?->partner?->company_name ?? '—')
```

### Widget Property Types (Filament v4)

```php
// Static vs non-static must match parent class exactly:
protected static int $sort;         // $sort     → STATIC ✅
protected array|string|int $columnSpan; // $columnSpan → non-static ✅
protected ?string $heading;         // ChartWidget $heading → non-static ✅
protected ?string $pollingInterval; // StatsOverviewWidget → non-static ✅
// Mixing these causes fatal "Cannot redeclare [non-]static" errors
```

---

## 7. Adding a New Feature — Step-by-Step

### A. New Filament Resource

```bash
# 1. Create the resource in the correct panel namespace
php artisan make:filament-resource Admin/Resources/MyThing/MyThingResource

# 2. Break into modular structure (follow project convention):
#    Resources/MyThing/
#    ├── MyThingResource.php
#    ├── Pages/{List,Create,Edit,View}MyThing.php
#    ├── Schemas/MyThingForm.php
#    ├── Tables/MyThingTable.php
#    └── RelationManagers/  (if needed)

# 3. Set navigation group and access control:
public static function getNavigationGroup(): string { return 'My Group'; }
public static function canAccess(): bool {
    return auth()->user()->hasAnyRole(['super_admin', 'admin']);
}
```

### B. New Migration

```bash
# Always use descriptive names
php artisan make:migration add_my_field_to_bookings_table
php artisan make:migration create_my_pivot_table

# Run and push:
php artisan migrate
git add database/migrations/
git commit -m "feat: add my_field to bookings"
```

### C. New Setting Group

```bash
# 1. Create the settings class
php artisan make:settings MyNewSettings

# 2. Add public properties for each setting field
# 3. Run discovery
php artisan settings:discover

# 4. Create a Filament page to edit it
# app/Filament/Admin/Pages/Settings/MyNewSettingsPage.php

# 5. Seed default values in SettingsSeeder
```

### D. New Notification

```bash
php artisan make:notification MyNewNotification
```

```php
// Template for all notifications in this project:
class MyNewNotification extends Notification implements ShouldQueue
{
    public string $queue = 'notifications';

    public function __construct(private readonly MyModel $model) {}

    public function via($notifiable): array { return ['mail']; }

    public function toMail($notifiable): MailMessage
    {
        $settings = app(AppSettings::class);
        return (new MailMessage)
            ->subject("Subject here — {$this->model->ref}")
            ->greeting("Hello,")
            ->line("Body text here.")
            ->action('View Details', url('/admin/...'))
            ->line("— {$settings->company_name}");
    }
}
```

---

## 8. Adding a New Role / Portal

Follow this checklist to add a new role with its own Filament panel:

```bash
# Step 1: Add role to seeder
# In database/seeders/RolesAndPermissionsSeeder.php → $roles[] array

# Step 2: Create the Panel Provider
php artisan make:filament-panel MyRolePanelProvider

# Step 3: Configure the provider
#   - Set panel ID, path, branding color
#   - discoverResources from 'App\\Filament\\MyRole\\**'
#   - Register ApplyEmailSettings middleware

# Step 4: Update canAccessPanel() in User.php
#   - Add: 'my-role' => $this->hasRole('my_role'),

# Step 5: Create panel directory structure
#   app/Filament/MyRole/
#   ├── Pages/MyRoleDashboard.php
#   ├── Resources/          (scoped resources)
#   └── Widgets/            (dashboard widgets)

# Step 6: Register in config/app.php or bootstrap/providers.php
# Step 7: Test login with a user of that role
```

---

## 9. Notification System

### Notification Classes

| Class | Trigger | Channel | Queue |
|-------|---------|---------|-------|
| `PartnerBookingNotification` | Partner booking created | Email → admin | notifications |
| `InvoiceIssuedNotification` | Invoice generated/sent | Email → partner (PDF attached) | notifications |
| `BookingConfirmedNotification` | Booking confirmed (pending→confirmed) | Email → partner | notifications |
| `DispatchAssignedNotification` | Dispatch created | Email → transport company | notifications |
| `DriverAssignedNotification` | Dispatch created | Email → each driver | notifications |
| WhatsApp (Twilio, direct) | Manual "Send WhatsApp" action | Twilio API | synchronous |

### Queue Worker (Required)

Notifications use `ShouldQueue`. The queue worker **must** be running:

```bash
php artisan queue:work --queue=notifications,default --sleep=3 --tries=3
```

> ⚠️ **Critical:** The queue worker is a separate PHP process — it never runs HTTP middleware. The `Queue::before()` hook in `AppServiceProvider` applies DB-based SMTP settings to queued jobs. Without it, queued emails use `.env` defaults instead of DB settings.

### WhatsApp via Twilio

```php
// WhatsAppSettings guards all WhatsApp sends:
$whatsapp = app(WhatsAppSettings::class);
if (!$whatsapp->enabled || empty($whatsapp->account_sid)) return;

// Phone number format: must start with +country_code
// Stored in drivers.phone — normalize before sending
```

---

## 10. Settings System

### Setting Groups

| Class | Group | Key Fields |
|-------|-------|------------|
| `AppSettings` | app | company_name, company_email, company_phone, address |
| `EmailSettings` | email | host, port, username, password, encryption, from_address, from_name |
| `WhatsAppSettings` | whatsapp | account_sid, auth_token, from_number, enabled |
| `PaxSettings` | pax | daily_pax_capacity (250), warning_threshold (20) |
| `BankSettings` | bank | bank_name, holder_name, account_number, iban, swift |
| `LegalSettings` | legal | if_number, ice, cnss, patente, rc |
| `NotificationSettings` | notifications | booking_confirmed_enabled, etc. |

### The Mail Config Pattern

```php
// app/Support/MailConfig.php
// Converts EmailSettings DB record → Laravel mail config at runtime
// Uses Mail::forgetMailers() to force fresh SMTP transport creation
// ssl → scheme = 'smtps' (NOT 'encryption' — Symfony Mailer API)
// tls → scheme = null (STARTTLS is default)
```

---

## 11. Common Pitfalls (Filament v4)

| ❌ Wrong | ✅ Correct | Why |
|---------|-----------|-----|
| `use Filament\Forms\Get` | `use Filament\Schemas\Components\Utilities\Get` | v4 moved reactive utilities |
| `use Filament\Tables\Actions\ViewAction` | `use Filament\Actions\ViewAction` | `Tables\Actions` namespace removed |
| `protected static string $view` on Page | `protected string $view` (non-static) | PHP inheritance type mismatch |
| `static $heading` on ChartWidget | `protected ?string $heading` (non-static) | Parent uses non-static |
| `static $columnSpan` on Widget | `protected array\|string\|int $columnSpan` | Must match parent union type |
| `TextInput::default()` for relationships on Edit | `Placeholder::make()->content(fn($record) => ...)` | `default()` is create-only |
| `ManageRelatedRecords` custom blade with `$this->selectedXxx` | Keep blade as bare `<x-filament-panels::page>` wrapper | Component renders the table natively |
| Custom Blade view → `getTableRecordKey()` missing on aggregate | Override `getTableRecordKey(Model\|array $record): string` | Aggregates have no `id` |
| `$this->ownerRecord` in `getTableQuery()` during Livewire AJAX | Guard with null check or re-fetch from DB using record ID | Component partially hydrates on re-render |
| `AppSettings::$email` | `AppSettings::$company_email` | Actual property name |

---

## 12. Git & Deployment Workflow

### Branching

```
main        → production-ready code, always deployable
feature/*   → new features in development
fix/*       → bug fixes
```

### Commit Message Convention

```
feat: add BookingConfirmedNotification
fix: correct AppSettings company_email property reference
refactor: split DispatchForm into configure() and forEdit()
chore: update PROGRESS.md for Phase 26-A
docs: add WORKFLOW.md developer guide
```

### Daily Development Loop

```bash
# 1. Pull latest
git pull origin main

# 2. Make changes
# (run php artisan serve + queue worker in background)

# 3. Stage & review changes
git status
git diff

# 4. Commit
git add -p    # stage interactively, review each chunk
git commit -m "feat/fix/refactor: description"

# 5. Push
git push origin main
```

### Before Committing a Migration

```bash
# Always migrate first — verify no errors
php artisan migrate

# Then commit both the migration AND any model/seeder changes together
git add database/migrations/XXXX_my_migration.php app/Models/MyModel.php
git commit -m "feat: add my_field migration and model update"
```

### Production Deployment Checklist

```bash
# On the server (Coolify / Contabo VPS):
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build                           # Rebuild Filament themes
php artisan settings:discover
supervisorctl restart queue-worker      # Restart queue worker
```

> ⚠️ **Filament custom themes** must be rebuilt on every deployment:  
> `php artisan make:filament-theme {panel}` was used once; after that just `npm run build`.

---

## 13. Current Phase Status

| Phase | Name | Status |
|-------|------|--------|
| 1 | Foundation | ✅ Complete |
| 2 | Settings & Config | ✅ Complete |
| 3 | User Management | ✅ Complete |
| 4 | Product Management | ✅ Complete |
| 5 | Partner Management | ✅ Complete |
| 6 | Transport Management | ✅ Complete |
| 7 | Regular Booking System | ✅ Complete |
| 8 | Partner Booking System | ✅ Complete |
| 9 | Dispatch System | ✅ Complete |
| 10 | Greeter Module | ✅ Complete |
| 11 | Accountant Module | ✅ Complete |
| 12 | Invoicing System | ✅ Complete |
| 13 | Financial Reports | ✅ Complete |
| 14 | Notifications & Automation | ✅ Complete |
| 15 | Partner Portal | ✅ Complete |
| 16 | Transport Portal | ✅ Complete |
| 17 | Driver Portal | ✅ Complete |
| 18 | Greeter Portal | ✅ Complete |
| 19 | Accountant Portal (`/accountant` — Finance Portal, Blue) | ✅ Complete |
| 20 | Manager Portal | ✅ Complete |
| 21 | Polish & Advanced Features | 🔲 Pending |
| 22 | PDF & Dashboard Enhancements | ✅ Complete |
| 23 | Guide Portal | ✅ Complete |
| 24 | Booking Calendar | ✅ Complete |
| 25 | Finance Reporting Optimization | ✅ Complete |
| 26 | Notification System Overhaul | 🔶 Partial (26-A done) |
| 27 | MinIO Backup System | 🔲 In Planning |
| 28 | Dispatcher Portal | ✅ Complete |

### Next Up

**Phase 26 (remaining sub-phases):**
- `26-B` — Unified driver WhatsApp (single send from dispatch page)
- `26-C` — Booking cancellation notification → partner
- `26-D` — PAX capacity alert notification → admin

**Phase 27:**
- MinIO S3-compatible self-hosted backup via `spatie/laravel-backup`
- Architecture: VPS (Coolify/Contabo) → Docker MinIO → Tailscale transfer

**Phase 21 (remaining):**
- Activity log viewer (Spatie)
- Global search across bookings
- Bulk confirm/cancel/export operations
- CSV import for bulk bookings
- Database performance indexes on `flight_date`, `booking_status`, `partner_id`

---

*Maintained by: Adventure Balloon dev team*  
*For architectural decisions and phase details, see [`PROGRESS.md`](./PROGRESS.md) and [`docs/phases/`](./phases/).*
