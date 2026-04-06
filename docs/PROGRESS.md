# Booklix — Development Progress Tracker
> **Last Updated:** 2026-04-06  
> **Stack:** Laravel 12 · Filament 4 · MySQL 8 · Spatie Suite  
> **App URL (dev):** http://127.0.0.1:8000  
> **Admin Panel:** http://127.0.0.1:8000/admin

---

## 🗺️ Phase Overview

| # | Phase | Priority | Est. Days | Status |
|---|-------|----------|-----------|--------|
| 1 | [Foundation](#phase-1--foundation) | — | 2–3 | ✅ **COMPLETE** |
| 2 | [Settings & Config](#phase-2--settings--config) | 🔴 HIGH | 2–3 | ✅ **COMPLETE** |
| 3 | [User Management](#phase-3--user-management) | 🔴 HIGH | 2–3 | ✅ **COMPLETE** |
| 4 | [Product Management](#phase-4--product-management) | 🔴 HIGH | 3–4 | ⏳ **NEXT** |
| 5 | [Partner Management](#phase-5--partner-management) | 🟠 MED-HIGH | 3–4 | 🔲 Pending |
| 6 | [Transport Management](#phase-6--transport-management) | 🟠 MED-HIGH | 4–5 | 🔲 Pending |
| 7 | [Regular Booking System](#phase-7--regular-booking-system) | 🔴 HIGH | 7–10 | 🔲 Pending |
| 8 | [Partner Booking System](#phase-8--partner-booking-system) | 🟡 MEDIUM | 3–4 | 🔲 Pending |
| 9 | [Dispatch System](#phase-9--dispatch-system) | 🟠 MED-HIGH | 5–7 | 🔲 Pending |
| 10 | [Greeter Module](#phase-10--greeter-module) | 🟡 MEDIUM | 2–3 | 🔲 Pending |
| 11 | [Accountant Module](#phase-11--accountant-module) | 🔴 HIGH | 3–4 | 🔲 Pending |
| 12 | [Invoicing System](#phase-12--invoicing-system) | 🟠 MED-HIGH | 4–5 | 🔲 Pending |
| 13 | [Financial Reports](#phase-13--financial-reports--dashboard) | 🟡 MEDIUM | 4–5 | 🔲 Pending |
| 14 | [Notifications & Automation](#phase-14--notifications--automation) | 🟡 MEDIUM | 3–4 | 🔲 Pending |
| 15 | [Polish & Advanced Features](#phase-15--polish--advanced-features) | 🟢 LOW | 3–5 | 🔲 Pending |
| | **TOTAL** | | **~53–70 days** | |

---

## Phase 1 — Foundation
📁 Details: [`docs/phases/phase-01-foundation.md`](phases/phase-01-foundation.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-05

### Completed ✅
- [x] Laravel 12 installed
- [x] `.env` configured (MySQL `booklix` DB, XAMPP)
- [x] `booklix` database created
- [x] Filament 4 installed (`filament/filament ^4.0`)
- [x] Spatie Permission installed (`^6.25` for PHP 8.2)
- [x] Spatie Settings installed (`^3.7`)
- [x] Spatie Medialibrary installed (`^11.21`)
- [x] Spatie Activitylog installed (`^4.12` for PHP 8.2)
- [x] DomPDF installed (`barryvdh/laravel-dompdf ^3.1`)
- [x] Maatwebsite Excel installed (`^3.1`)
- [x] Filament admin panel scaffolded (panel ID: `admin`, path: `/admin`)
- [x] All Spatie migrations published & run
- [x] `User` model upgraded with `HasRoles` trait + `canAccessPanel()`
- [x] `RolesAndPermissionsSeeder` — 8 roles + all permissions
- [x] `AdminUserSeeder` — super_admin user seeded
- [x] Admin panel verified — login working at http://127.0.0.1:8000/admin

---

## Phase 2 — Settings & Config
📁 Details: [`docs/phases/phase-02-settings.md`](phases/phase-02-settings.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-05
**Est. Days:** 3–4

### Setting Groups (6 total)
- [x] **`AppSettings`** — company name, email, phone, address, logo upload
- [x] **`LegalSettings`** — IF (Identifiant Fiscal), CNSS, Patente, RC (Registre de Commerce), ICE
- [x] **`PaxSettings`** — daily PAX capacity (default 250) + warning threshold (default 20)
- [x] **`BankSettings`** — bank name, holder name, account number, IBAN, Swift, routing number
- [x] **`EmailSettings`** — SMTP host, port, credentials, encryption, from address/name
- [x] **`WhatsAppSettings`** — Twilio account_sid, auth_token, from_number, enabled flag

### Filament Pages
- [x] `AppSettingsPage` — general info + logo upload (Spatie Media Library)
- [x] `LegalSettingsPage` — all 5 Moroccan legal identifier fields
- [x] `PaxSettingsPage` — capacity + warning threshold (number inputs)
- [x] `BankSettingsPage` — 6 bank fields (used on PDF invoices)
- [x] `EmailSettingsPage` + "Send Test Email" action
- [x] `WhatsAppSettingsPage` + "Send Test WhatsApp" action

### Dashboard Widget
- [x] `PaxAlertWidget` — shows warning/critical when remaining PAX ≤ threshold

### Infrastructure
- [x] Run `php artisan settings:discover`
- [x] `ApplyEmailSettings` middleware (override `config('mail')` from DB)
- [x] `SettingsSeeder` — seeds all 6 groups with defaults
- [x] All settings pages restricted to `super_admin` only (navigation group)

---

## Phase 3 — User Management
📁 Details: [`docs/phases/phase-03-users.md`](phases/phase-03-users.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-06

### Completed ✅
- [x] KYC migration: `phone`, `national_id`, `nationality`, `date_of_birth`, `address`, `is_active`, `last_login_at` added to `users` table
- [x] `User` model updated: `HasMedia` + `InteractsWithMedia` traits, KYC `$fillable` + casts, `getFilamentAvatarUrl()`, `canAccessPanel()` blocks inactive users
- [x] `UpdateLastLogin` listener — tracks `last_login_at` on every `Auth\Login` event
- [x] `filament/spatie-laravel-media-library-plugin` installed for avatar UI components
- [x] `UserResource` — full CRUD with navigation group "User Management", restricted to `super_admin` + `admin`
- [x] `UserForm` — Profile section (avatar upload, name, email, password), KYC section (phone, national_id, nationality, DOB, address), Access Control section (is_active toggle, roles multi-select with `super_admin` masked from admins)
- [x] `UsersTable` — circular avatar column, role badges, `is_active` toggle, searchable columns
- [x] `UserInfolist` — sectioned view page with: Profile, KYC Data, System Variables, and **Computed Permissions** (reads `getAllPermissions()` dynamically)
- [x] Fixed `.env`: `APP_URL=http://127.0.0.1:8000`, `FILESYSTEM_DISK=public`, `MEDIA_DISK=public` to resolve avatar CORS issue
- [x] `php artisan storage:link` verified — media files served via `public/storage`
- [x] Pushed to GitHub: `https://github.com/9-shen/adventure-balloon`

### Known Limitations
- User activity log viewer (Spatie Activitylog) deferred to Phase 15
- Avatar upload tested visually; automated upload test skipped (browser file picker restriction)

---

## Phase 4 — Product Management
📁 Details: [`docs/phases/phase-04-products.md`](phases/phase-04-products.md)  
**Status: 🔲 Pending**

### To Do
- [ ] `products` table migration (`name`, `description`, `base_adult_price`, `base_child_price`, `max_pax`, `duration_minutes`, `is_active`)
- [ ] `blackout_dates` table migration
- [ ] `ProductResource` Filament CRUD
- [ ] Multiple product images (Spatie Media Library)
- [ ] Availability calendar widget
- [ ] Blackout date management
- [ ] `ProductAvailabilityService`

---

## Phase 5 — Partner Management
📁 Details: [`docs/phases/phase-05-partners.md`](phases/phase-05-partners.md)  
**Status: 🔲 Pending**

### To Do
- [ ] `partners` table migration (company info, KYC, banking)
- [ ] `partner_products` pivot table (custom adult + child pricing)
- [ ] `PartnerResource` Filament CRUD
- [ ] KYC document upload
- [ ] Partner status workflow (pending → approved → rejected)
- [ ] Custom pricing per product per partner
- [ ] Link partner users to partner company

---

## Phase 6 — Transport Management
📁 Details: [`docs/phases/phase-06-transport.md`](phases/phase-06-transport.md)  
**Status: 🔲 Pending**

### To Do
- [ ] `transport_companies` table migration
- [ ] `vehicles` table migration (make, model, plate, capacity, type, price)
- [ ] `drivers` table migration (personal info, license, WhatsApp)
- [ ] `driver_vehicle` pivot (with `is_default` flag)
- [ ] Filament resources for all three models
- [ ] Transport user account linking

---

## Phase 7 — Regular Booking System
📁 Details: [`docs/phases/phase-07-regular-booking.md`](phases/phase-07-regular-booking.md)  
**Status: 🔲 Pending**

### To Do
- [ ] `bookings` table migration (unified — see schema)
- [ ] `customers` table migration (per-PAX details)
- [ ] 5-step Filament wizard `CreateBooking`
  - [ ] Step 1: Flight details (product, date, PAX count + PAX check)
  - [ ] Step 2: Customer details (form per PAX)
  - [ ] Step 3: Pricing & discounts
  - [ ] Step 4: Payment info
  - [ ] Step 5: Review & confirm
- [ ] `BookingService` (create, confirm, cancel, calculateTotal, checkAvailability)
- [ ] Booking reference generator (`BLX-YYYY-XXXX`)
- [ ] `BookingResource` with status management

---

## Phase 8 — Partner Booking System
📁 Details: [`docs/phases/phase-08-partner-booking.md`](phases/phase-08-partner-booking.md)  
**Status: 🔲 Pending**

### To Do
- [ ] Partner Booking wizard (reuses Phase 7 wizard, partner prices auto-loaded)
- [ ] Partner panel (`/partner`) Filament setup
- [ ] `type = 'partner'` + `partner_id` stored in unified bookings table
- [ ] Reference generator (`PBX-YYYY-XXXX`)

---

## Phase 9 — Dispatch System
📁 Details: [`docs/phases/phase-09-dispatch.md`](phases/phase-09-dispatch.md)  
**Status: 🔲 Pending**

### To Do
- [ ] `dispatches` table migration
- [ ] `dispatch_drivers` pivot migration
- [ ] `DispatchResource` Filament CRUD
- [ ] Transport company assignment
- [ ] Driver auto-assignment algorithm (`ceil(pax / capacity)`)
- [ ] Transporter email notification (manifest)
- [ ] Driver WhatsApp notification (Twilio)
- [ ] Status tracking (Pending → Confirmed → In Progress → Delivered)
- [ ] `DispatchService`

---

## Phase 10 — Greeter Module
📁 Details: [`docs/phases/phase-10-greeter.md`](phases/phase-10-greeter.md)  
**Status: 🔲 Pending**

### To Do
- [ ] `/greeter` Filament panel setup
- [ ] Today's bookings list
- [ ] 7-day calendar view
- [ ] Attendance toggle (Show / No-Show) per customer
- [ ] Booking history with search
- [ ] Greeter dashboard stats widget

---

## Phase 11 — Accountant Module
📁 Details: [`docs/phases/phase-11-accountant.md`](phases/phase-11-accountant.md)  
**Status: 🔲 Pending**

### To Do
- [ ] Accountant access to `/admin` (scoped view)
- [ ] Financial overview (all bookings + payment status)
- [ ] Payment adjustment capability
- [ ] Attendance verification cross-check
- [ ] Revenue summary by day/week/month
- [ ] Due payments list
- [ ] Filament widgets: TotalRevenue, OutstandingBalance, PaymentsByMethod, RecentPayments

---

## Phase 12 — Invoicing System
📁 Details: [`docs/phases/phase-12-invoicing.md`](phases/phase-12-invoicing.md)  
**Status: 🔲 Pending**

### To Do
- [ ] `invoices` table migration
- [ ] `invoice_items` table migration
- [ ] `InvoiceResource` Filament CRUD
- [ ] Invoice generation from partner bookings (batch by period)
- [ ] PDF generation (DomPDF) with company branding
- [ ] Email invoice to partner
- [ ] Mark invoice as paid
- [ ] `InvoiceService`

---

## Phase 13 — Financial Reports & Dashboard
📁 Details: [`docs/phases/phase-13-reports.md`](phases/phase-13-reports.md)  
**Status: 🔲 Pending**

### To Do
- [ ] Revenue report (Regular vs Partner, by date range)
- [ ] Transport cost report
- [ ] Due payments report
- [ ] Client statistics (repeat customers, nationality)
- [ ] PAX & flight stats (volume, no-show rate)
- [ ] CSV export via Maatwebsite Excel for all reports

---

## Phase 14 — Notifications & Automation
📁 Details: [`docs/phases/phase-14-notifications.md`](phases/phase-14-notifications.md)  
**Status: 🔲 Pending**

### To Do
- [ ] `BookingConfirmedNotification` → customer email
- [ ] `BookingCanceledNotification` → customer email
- [ ] `DispatchAssignedNotification` → transporter email
- [ ] `DriverAssignedNotification` → driver WhatsApp (Twilio)
- [ ] `InvoiceIssuedNotification` → partner email + PDF
- [ ] `PaymentReminderNotification` → partner email
- [ ] Queue jobs for all async notifications
- [ ] Notification log in Filament
- [ ] Retry failed notifications

---

## Phase 15 — Polish & Advanced Features
📁 Details: [`docs/phases/phase-15-polish.md`](phases/phase-15-polish.md)  
**Status: 🔲 Pending**

### To Do
- [ ] Activity log viewer in Filament (Spatie)
- [ ] Global search across bookings
- [ ] Bulk operations (confirm, cancel, export)
- [ ] CSV import for bulk bookings
- [ ] Mobile optimization (greeter + driver panels)
- [ ] Widget visibility by role
- [ ] Audit trail reports

---

## 📐 Architecture Flow

```
Phase 1: Foundation ✅
    ↓
Phase 2: Settings & Config ✅
    ↓
Phase 3: User Management ✅
    ↓
Phase 4: Product Management ← NEXT
    ↓               ↓
Phase 5: Partners  Phase 6: Transport
    ↓               ↓
Phase 7: Regular Bookings
    ↓
Phase 8: Partner Bookings
    ↓
Phase 9: Dispatch System
    ↓               ↓
Phase 10: Greeter  Phase 11: Accountant
    ↓               ↓
    Phase 12: Invoicing
        ↓
    Phase 13: Reports
        ↓
    Phase 14: Notifications
        ↓
    Phase 15: Polish & Advanced
```

---

## 🔑 Key Info

| Item | Value |
|------|-------|
| Admin URL | http://127.0.0.1:8000/admin |
| Admin Email | webmaster@9-shen.com |
| DB Name | booklix |
| DB User | root |
| DB Host | 127.0.0.1:3306 (XAMPP) |
| Filament Version | v4.0.0 |
| Laravel Version | 12.x |
| PHP Version | 8.2.12 |
