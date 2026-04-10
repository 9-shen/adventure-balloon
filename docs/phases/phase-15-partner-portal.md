# Phase 15 — Partner Portal
**Status: ✅ COMPLETE**  
**Priority:** 🟠 MEDIUM-HIGH  
**Depends On:** Phases 5, 8, 12 (Partners, Partner Booking, Invoicing)  
**Completed:** 2026-04-10  
**Est. Days:** 2–3

---

## Goal
A dedicated, isolated Filament panel at `/partner` where partner agencies can:
- Log in with their own credentials
- Manage their company profile
- Create and view their bookings (3-step wizard)
- View their invoices (read-only)
- Check their account statement (stats + CSV exports)

Admin staff retain full control via `/admin`. Partner users **cannot** access the admin panel.

---

## Database Changes

- [x] `2026_04_10_202504_add_partner_id_to_users_table` — adds `partner_id` nullable FK on `users` table (unsigned bigint, `nullOnDelete`)
- [x] Removed old `partner_users` pivot table concept — replaced with direct FK on `users`

---

## Models Updated

- [x] `User` model — `partner_id` added to `$fillable`; `partner()` BelongsTo relationship added
- [x] `Partner` model — `users()` HasMany relationship added (via `partner_id` FK)

---

## Admin Panel Integration (PartnerResource)

- [x] **Portal Access section** added to `PartnerForm` — "Assign Portal User" select (searchable, shows all users without a partner_id already assigned, or the current linked user)
- [x] `CreatePartner` — `mutateFormDataBeforeCreate()` strips virtual `portal_user_id` from data; `afterCreate()` assigns `partner_id` FK on the selected user
- [x] `EditPartner` — same `mutateFormDataBeforeSave()` / `afterSave()` hooks; handles user reassignment or detachment

---

## Partner Panel Provider

- [x] `app/Providers/Filament/PartnerPanelProvider.php` created
  - Panel ID: `partner`, Path: `/partner`
  - Branding: teal (`#0e7490`) primary color, `Booklix Partner Portal` name
  - Auth: default Filament login guard, redirect to `/partner` after login
  - Auto-discovers resources from: `App\Filament\Partner\Resources\**`
  - Auto-discovers pages from: `App\Filament\Partner\Pages\**`
  - Auto-discovers widgets from: `App\Filament\Partner\Widgets\**`
- [x] Registered in `bootstrap/providers.php`

---

## Access Control

- [x] `User::canAccessPanel()` updated:
  - `/partner` panel: requires `role = partner` AND `partner_id !== null`
  - `/admin` panel: allows `super_admin`, `admin`, `manager`, `accountant`, `greeter`, `transport`, `driver`
  - Partner role users **cannot** access `/admin`; admin staff **cannot** access `/partner`

---

## Partner Portal — Pages

### Dashboard
- [x] `PartnerStatsWidget` — 4 stat cards scoped to `Auth::user()->partner_id`:
  - Total Bookings (partner type), Total PAX, Confirmed Bookings, Total Revenue (final_amount sum)
- [x] Default Filament dashboard with `PartnerStatsWidget` registered

### Profile Page (`/partner/profile`)
- [x] `app/Filament/Partner/Pages/Profile.php`
- [x] Non-static `protected string $view` (important: NOT static — prevents PHP fatal)
- [x] `InteractsWithForms` + `HasForms` pattern
- [x] 2 sections: Company Information (editable) + Banking Details (editable)
- [x] Fields: company_name, trade_name, email, phone, address, city, country, bank_name, bank_account, bank_iban, bank_swift, payment_terms_days
- [x] `save()` Livewire action — updates partner record, shows success notification

### Account Statement (`/partner/account-statement`)
- [x] `app/Filament/Partner/Pages/AccountStatement.php`
- [x] Implements `HasTable` + `InteractsWithTable`
- [x] `getHeaderWidgets()` → `AccountStatsWidget` (4 cards: bookings, billed, paid, outstanding)
- [x] Tab switcher (Bookings / Invoices) via `wire:click → switchTab($tab) → resetTable()`
- [x] `table()` method returns either `buildBookingsTable()` or `buildInvoicesTable()` based on `$activeTab`
- [x] **Bookings table**: booking_ref, flight_date, product, adults, children, amount, payment badge, status badge; filters: booking_status, payment_status
- [x] **Invoices table**: invoice_ref, period, subtotal, tax, total, status badge, sent_at, paid_at; filter: status
- [x] Header actions: **Export Bookings CSV** + **Export Invoices CSV** (stream download with date-stamped filename)

---

## Partner Portal — Resources

### My Bookings (`/partner/bookings/partner-bookings`)
- [x] `app/Filament/Partner/Resources/Bookings/PartnerBookingResource.php`
- [x] `getEloquentQuery()` scopes to `partner_id = Auth::user()->partner_id` and `type = 'partner'`
- [x] Navigation group: "My Bookings" (sort 1)
- [x] List, Create (wizard), View pages only — no Edit/Delete

#### Create Partner Booking — 3-Step Wizard
- [x] **Step 1 — Flight Details:**
  - Product select (filtered to `partner_products` pivot for this partner only — uses `whereHas` join)
  - Flight date picker with PAX availability hint (live via BookingService)
  - Adult PAX + Child PAX (min 1 adult)
  - Flight time (optional)
- [x] **Step 2 — Passengers:**
  - Repeater: one entry per PAX (adult/child type, full_name, email, phone, nationality, passport optional, DOB optional, weight optional, is_primary flag)
  - itemLabel shows full name on collapse
- [x] **Step 3 — Review:**
  - Placeholders: product, date, adult count, child count, partner adult price × adults, partner child price × children, total amount
  - Notes textarea
- [x] **`afterCreate()` hook:**
  - Sets `type = 'partner'`, `partner_id`, auto-generates PBX ref via `BookingService::generateRef('PBX')`
  - Applies partner pricing from `partner_products` pivot
  - Fires `PartnerBookingNotification` to admin email (try/catch, non-blocking)
  - Redirects to view page with success notification

#### List Partner Bookings
- [x] Columns: booking_ref (monospace), flight_date, product, adult_pax, child_pax, final_amount, payment_status badge, booking_status badge
- [x] Filters: booking_status, payment_status
- [x] Read-only — no edit/delete/cancel actions exposed

#### View Partner Booking
- [x] Infolist: Booking Info section + Passenger List (RepeatableEntry)

### Invoices (`/partner/invoices/partner-invoices`)
- [x] `app/Filament/Partner/Resources/Invoices/PartnerInvoiceResource.php`
- [x] `getEloquentQuery()` scopes to `partner_id = Auth::user()->partner_id`
- [x] Navigation group: "My Bookings" (sort 2)
- [x] **Read-only** — no create action (partners cannot issue their own invoices)
- [x] Columns: invoice_ref, period, subtotal, tax, total_amount, status badge, sent_at, paid_at
- [x] Filter: status (draft/sent/paid/overdue)

---

## Widgets

- [x] `app/Filament/Partner/Widgets/PartnerStatsWidget.php` — Dashboard: 4 stats (bookings, confirmed bookings, total PAX, total revenue)
- [x] `app/Filament/Partner/Widgets/AccountStatsWidget.php` — Account Statement header: 4 stats (total bookings, total billed, total paid, outstanding/overdue)

---

## Roles Update

Alongside this phase, the canonical role list was updated:

| Role | Panel | Status |
|------|-------|--------|
| super_admin | /admin | ✅ Kept |
| admin | /admin | ✅ Kept |
| manager | /admin | ✅ Kept |
| accountant | /admin | ✅ Kept |
| greeter | /admin | ✅ Kept |
| transport | /admin | ✅ **NEW** (was: dispatcher) |
| driver | /admin | ✅ **NEW** (was: pilot) |
| partner | /partner | ✅ Kept |
| agent | — | ❌ Removed |
| dispatcher | — | ❌ Removed |
| pilot | — | ❌ Removed |
| customer | — | ❌ Removed |

`RolesAndPermissionsSeeder` now deletes obsolete roles on every run.

---

## Bug Fixes (This Phase)

- [x] **`AppSettings::$email` does not exist** — The correct property is `company_email`. Fixed in 3 files:
  - `CreatePartnerBooking::afterCreate()` — prevented 500 on every partner booking creation
  - `CreateBooking::afterCreate()` (admin) — same fix for admin-side partner bookings
  - `InvoiceIssuedNotification::toMail()` — prevented 500 on invoice send

---

## Filament v4 Gotchas (Discovered This Phase)

- `protected string $view` (non-static instance property) → **correct** — matches `Filament\Pages\Page::$view`
- `protected static string $view` → **FATAL ERROR** — PHP can't redeclare a non-static parent property as static
- `getView(): string` method → works BUT return type must EXACTLY match parent signature
- `getNavigationIcon()` must be a **method** (not `protected static ?string $navigationIcon`) in Resources in Filament v4
- Tab switching with `resetTable()` in `InteractsWithTable` pages — required to reset paginator and filters when switching between two different model queries

---

## Routes Added

```
GET /partner                         → Dashboard
GET /partner/login                   → Partner Login
GET /partner/profile                 → Profile Page
GET /partner/account-statement       → Account Statement (stats + tabs table + CSV)
GET /partner/bookings/partner-bookings        → My Bookings list
GET /partner/bookings/partner-bookings/create → Create Booking (wizard)
GET /partner/bookings/partner-bookings/{id}   → View Booking
GET /partner/invoices/partner-invoices        → My Invoices list
```
