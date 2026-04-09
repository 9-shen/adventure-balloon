# Booklix — Development Progress Tracker

> **Last Updated:** 2026-04-09 (Phase 12 — Invoicing System: PartnerInvoiceResource, ViewPartnerBookings with date range filter + multi-select invoice basket, InvoiceResource with PDF download + Mark Paid, InvoiceService, DomPDF template)  
> **Stack:** Laravel 12 · Filament 4 · MySQL 8 · Spatie Suite  
> **App URL (dev):** http://127.0.0.1:8000  
> **Admin Panel:** http://127.0.0.1:8000/admin

---

## 🗺️ Phase Overview

| #   | Phase                                                              | Priority    | Est. Days       | Status          |
| --- | ------------------------------------------------------------------ | ----------- | --------------- | --------------- |
| 1   | [Foundation](#phase-1--foundation)                                 | —           | 2–3             | ✅ **COMPLETE** |
| 2   | [Settings & Config](#phase-2--settings--config)                    | 🔴 HIGH     | 2–3             | ✅ **COMPLETE** |
| 3   | [User Management](#phase-3--user-management)                       | 🔴 HIGH     | 2–3             | ✅ **COMPLETE** |
| 4   | [Product Management](#phase-4--product-management)                 | 🔴 HIGH     | 3–4             | ✅ **COMPLETE** |
| 5   | [Partner Management](#phase-5--partner-management)                 | 🟠 MED-HIGH | 3–4             | ✅ **COMPLETE** |
| 6   | [Transport Management](#phase-6--transport-management)             | 🟠 MED-HIGH | 4–5             | ✅ **COMPLETE** |
| 7   | [Regular Booking System](#phase-7--regular-booking-system)         | 🔴 HIGH     | 7–10            | ✅ **COMPLETE** |
| 8   | [Partner Booking System](#phase-8--partner-booking-system)         | 🟡 MEDIUM   | 3–4             | ✅ **COMPLETE** |
| 9   | [Dispatch System](#phase-9--dispatch-system)                       | 🟠 MED-HIGH | 5–7             | 🔄 **IN PROGRESS** |
| 10  | [Greeter Module](#phase-10--greeter-module)                        | 🟡 MEDIUM   | 2–3             | ✅ **COMPLETE** |
| 11  | [Accountant Module](#phase-11--accountant-module)                  | 🔴 HIGH     | 3–4             | ✅ **COMPLETE** |
| 12  | [Invoicing System](#phase-12--invoicing-system)                    | 🔴 HIGH     | 4–5             | ✅ **COMPLETE** |
| 13  | [Financial Reports](#phase-13--financial-reports--dashboard)       | 🟡 MEDIUM   | 4–5             | 🔲 Pending      |
| 14  | [Notifications & Automation](#phase-14--notifications--automation) | 🟡 MEDIUM   | 3–4             | 🔲 Pending      |
| 15  | [Polish & Advanced Features](#phase-15--polish--advanced-features) | 🟢 LOW      | 3–5             | 🔲 Pending      |
|     | **TOTAL**                                                          |             | **~53–70 days** |                 |

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
**Status: ✅ COMPLETE** — Completed 2026-04-06

### Completed ✅

- [x] `products` migration — `name`, `description`, `base_adult_price`, `base_child_price`, `duration_minutes` (nullable), `is_active`, `deleted_at` (soft deletes). **No `max_pax`** — capacity is global via `PaxSettings::daily_pax_capacity`
- [x] `blackout_dates` migration — `product_id` nullable (NULL = global blackout), `date`, `reason`, unique constraint on `(product_id, date)`
- [x] `Product` model — `HasMedia`, `InteractsWithMedia`, `SoftDeletes`, `hasMany(BlackoutDate)`, media collection `'product-images'` with `thumb` conversion
- [x] `BlackoutDate` model — nullable `belongsTo(Product)`, query scopes: `scopeForDate()`, `scopeForProduct()`
- [x] `ProductAvailabilityService` — `isDateBlocked(?int $productId, Carbon $date): bool`, `getBlockedDatesForMonth(): Collection`
- [x] `ProductResource` — navigation group "Product Management", restricted to `super_admin` + `admin` + `manager`, soft delete with `getEloquentQuery()` scope
- [x] `ProductForm` — 4 sections: Basic Info (name, description), Pricing (adult price MAD, child price MAD side-by-side), Details (duration, is_active toggle), Product Images (multi-upload, reorderable, 10 max)
- [x] `ProductsTable` — thumbnail column, name, adult/child prices, active toggle, `TrashedFilter`, all soft delete actions
- [x] `ProductInfolist` — Basic Info, Pricing (money formatted), Details, Images, System sections
- [x] `BlackoutDatesRelationManager` — inline inside ProductResource edit/view page, `Add Blackout Date` button, date + reason fields
- [x] `ListProducts`, `CreateProduct`, `EditProduct`, `ViewProduct` pages scaffolded
- [x] Pushed to GitHub

### Architecture Decisions

- `max_pax` is intentionally **NOT** on the product — global cap lives in `PaxSettings::daily_pax_capacity`
- `ProductAvailabilityService` in Phase 4 = blackout dates ONLY; booking-based PAX check extended in Phase 7
- Folder structure: `Products/Schemas/`, `Tables/`, `Pages/`, `RelationManagers/` (mirrors Phase 3 User Management)

---

## Phase 5 — Partner Management

📁 Details: [`docs/phases/phase-05-partners.md`](phases/phase-05-partners.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-06

### Completed ✅

- [x] `partners` migration — `company_name`, `trade_name`, `registration_number`, `tax_number`, `email`, `phone`, `address`, `city`, `country`, `bank_name`, `bank_account`, `bank_iban`, `bank_swift`, `payment_terms_days`, `status` (enum: pending/approved/rejected), `approved_at`, `is_active`, `notes`, `deleted_at` (soft deletes)
- [x] `partner_products` pivot migration — `partner_id`, `product_id`, `partner_adult_price`, `partner_child_price`, `is_active`, unique `(partner_id, product_id)`
- [x] `Partner` model — `HasMedia`, `SoftDeletes`, `belongsToMany(Product)` via `PartnerProduct` pivot, media collections: `'kyc-documents'` (PDF+images) + `'partner-logo'` (single file)
- [x] `PartnerProduct` pivot model — `Pivot` class with `partner_adult_price`, `partner_child_price`, `is_active`, `belongsTo(Partner)` + `belongsTo(Product)`
- [x] `Product` model updated — `partners()` reverse `belongsToMany` relationship added
- [x] `PartnerResource` — modular structure: `Partners/Schemas/`, `Tables/`, `Pages/`, `RelationManagers/`, navigation group "Partner Management", access: `super_admin` + `admin` + `manager`
- [x] `PartnerForm` — 5 sections: Company Information (name, trade name, reg no., email, phone, city, country, address), Tax & Legal (collapsed), Banking Details (collapsed — bank name, account, IBAN, SWIFT, payment terms), Status & Account (status dropdown, is_active toggle, approved_at), KYC Documents (multi-upload PDF/images)
- [x] `PartnerInfolist` — read-only view with status badges (green=approved, red=rejected, orange=pending)
- [x] `PartnersTable` — status badge column, product count (`counts('products')`), `TrashedFilter`, full soft delete actions
- [x] `PartnerProductsRelationManager` — `AttachAction` (NOT `CreateAction`) inserts pivot rows with adult/child prices, `$recordTitleAttribute = 'name'` so dropdown shows product titles, `EditAction` for updating prices, `DetachAction` to remove
- [x] Pricing table columns: Product name, Base Adult (gray), Partner Adult, Base Child (gray), Partner Child, Active icon
- [x] Status workflow: `pending` → `approved` (sets `approved_at`) → `rejected`
- [x] Pushed to GitHub: `9-shen/adventure-balloon`

### Architecture Decisions

- `AttachAction` used on the pivot relation manager (not `CreateAction`) — avoids Eloquent trying to create a Product instead of a pivot row
- `$recordTitleAttribute = 'name'` on the RelationManager tells Filament's `AttachAction` which column to display as the dropdown label
- Partner pricing columns show **both** base price (gray, from `products` table) and partner override price side by side for quick reference
- `PartnerProduct` extends `Pivot` (not `Model`) with `$incrementing = true` since the pivot table has an `id` column

---

## Phase 6 — Transport Management

📁 Details: [`docs/phases/phase-06-transport.md`](phases/phase-06-transport.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-06

### Completed ✅

- [x] `transport_companies` migration — company name, contact, email, phone, address, bank details (name/account/IBAN), is_active, soft deletes
- [x] `vehicles` migration — `transport_company_id` FK, make, model, plate_number (unique), capacity, vehicle_type (enum: van/minibus/bus/car), price_per_trip, is_active, soft deletes
- [x] `drivers` migration — `transport_company_id` FK, name, phone (WhatsApp), national_id, license_number, license_expiry, is_active, soft deletes
- [x] `driver_vehicle` pivot migration — driver_id, vehicle_id, `is_default` flag, unique constraint on (driver_id, vehicle_id)
- [x] `TransportCompany` model — `HasMedia` (company-logo), `SoftDeletes`, `hasMany(Vehicle)`, `hasMany(Driver)`
- [x] `Vehicle` model — `SoftDeletes`, `belongsTo(TransportCompany)`, `belongsToMany(Driver)` via driver_vehicle pivot
- [x] `Driver` model — `HasMedia` (license-documents), `SoftDeletes`, `belongsTo(TransportCompany)`, `belongsToMany(Vehicle)`, `isLicenseExpiringSoon()` helper (red warning ≤30 days)
- [x] `TransportCompanyResource` — "Transport Management" nav group (sort 1), with `VehiclesRelationManager` + `DriversRelationManager` for inline management within company edit page
- [x] `VehicleResource` — standalone resource (sort 2), shows company name, plate badge, type badge with colors, seats, price/trip, driver count
- [x] `DriverResource` — standalone resource (sort 3), shows company, WhatsApp phone, license expiry (red if soon ≤30 days), vehicle count, license doc upload
- [x] All resources: soft delete support, `TrashedFilter`, role-based access (`super_admin`, `admin`, `manager`)
- [x] **Phase 6.1 — Driver-Vehicle Assignment:**
    - [x] `Vehicles/RelationManagers/DriversRelationManager` — `AttachAction` with same-company filter, `is_default` pivot toggle shown as "Default Driver" column
    - [x] `Drivers/RelationManagers/VehiclesRelationManager` — `AttachAction` with same-company filter, `is_default` pivot toggle shown as "Default Vehicle" column
    - [x] Registered `getRelations()` in both `VehicleResource` and `DriverResource`
    - [x] Green checkmark appears on both sides when `is_default = true`
- [x] Pushed to GitHub: `9-shen/adventure-balloon`

### Architecture Decisions

- Navigation uses **methods** (`getNavigationGroup()`, `getNavigationIcon()`) instead of static properties — PHP 8.2 strict type inheritance from Filament's `Resource` class forbids property overrides with incompatible types
- Bulk actions all from `Filament\Actions` namespace (NOT `Filament\Tables\Actions`) in Filament v4
- Vehicles and Drivers are accessible both as standalone resources AND inline via the TransportCompany edit page relation managers
- `Driver::isLicenseExpiringSoon()` renders license_expiry cells in red when within 30 days — visible on both list and relation manager tables
- `driver_vehicle` pivot has no custom Pivot model — `withPivot('is_default')` is sufficient since no extra logic needed

---

## Phase 7 — Regular Booking System

📁 Details: [`docs/phases/phase-07-regular-booking.md`](phases/phase-07-regular-booking.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-07

### Completed ✅

- [x] `bookings` migration — booking_ref (unique, BLX-YYYY-NNNN), type (regular/partner), product_id FK, flight_date/time, adult_pax/child_pax, booking_source, price snapshot (base_adult_price, base_child_price), totals (adult_total, child_total, discount_amount, final_amount), payment (method/status/amount_paid/balance_due), booking_status (pending/confirmed/cancelled/completed), audit columns (created_by/confirmed_by/cancelled_by + timestamps), soft deletes
- [x] `booking_customers` migration — booking_id FK (cascade delete), type (adult/child), full_name, email, phone, nationality, passport_number (optional), date_of_birth (optional), weight_kg (optional — balloon safety), is_primary flag
- [x] `Booking` model — SoftDeletes, all fillable, decimal casts, relationships (product, createdBy, confirmedBy, cancelledBy, customers hasMany), helpers (getTotalPax(), isPending(), isConfirmed(), isCancelled(), isCompleted(), getStatusColor(), getPaymentStatusColor())
- [x] `BookingCustomer` model — fillable, date/decimal casts, belongsTo(Booking)
- [x] `BookingService` with:
    - [x] `generateRef()` — BLX-YYYY-NNNN sequential per year, collision-safe
    - [x] `getAvailablePax(Carbon $date)` — 250 cap minus pending+confirmed bookings on that date
    - [x] `checkAvailability(Carbon $date, int $pax)` — returns bool
    - [x] `calculatePricing(Product, adultPax, childPax, discount)` — returns price snapshot array
    - [x] `createBooking(array $data)` — DB transaction: Booking::create() + customers loop
- [x] **5-step Bookings Wizard (`BookingWizard.php`):**
    - [x] Step 1 — Flight Details: product select (active only), date picker with live PAX availability hint (⚠️ warning if <20), time (optional), adult_pax/child_pax (live), booking source
    - [x] Step 2 — Customer Details: Placeholder showing expected PAX count + freeform Repeater (full_name, type, email, phone, nationality, passport optional, DOB optional, weight optional, is_primary toggle), itemLabel shows name on collapse
    - [x] Step 3 — Pricing & Discounts: live Placeholder for adult_total, child_total, final_amount (reads Product prices reactively via Get $get), discount_amount input, discount_reason
    - [x] Step 4 — Payment: payment_method (cash/wire/online), payment_status (due/partial/paid/on_site), amount_paid, live balance_due Placeholder
    - [x] Step 5 — Review & Confirm: summary Placeholders (product, date, PAX, source, method, final), notes Textarea
- [x] `BookingEditForm.php` — flat section-based edit form for EditBooking page (flight details, payment, status & notes)
- [x] `BookingsTable.php` — columns: ref badge (copyable), product, flight_date, adults, children, total (money), payment_status badge (colour-coded), booking_status badge (colour-coded), source; filters: status, payment_status, source, TrashedFilter; defaultSort: flight_date desc
- [x] `CreateBooking` page — overrides `form()` with wizard, `beforeCreate()` PAX availability check (halt + danger notification if exceeded), `mutateFormDataBeforeCreate()` computes all calculated fields + generates ref + sets created_by + type='regular', `handleRecordCreation()` delegates to BookingService::createBooking(), `afterCreate()` success notification with booking ref
- [x] `EditBooking` page — Confirm Booking header action (visible when pending, requires confirmation, sets confirmed_by/confirmed_at, redirects to view), Cancel Booking header action (modal with cancelled_reason field, sets cancelled_by/cancelled_at), DeleteAction, `mutateFormDataBeforeSave()` recalculates balance_due
- [x] `ViewBooking` page — standard view with Edit header action
- [x] `ListBookings` page — standard list with CreateAction header
- [x] `BookingCustomersRelationManager` — inline CRUD table for passengers on Edit page; form modal: all passenger fields; table: name (bold), type badge, email, phone, nationality, weight (toggleable), is_primary boolean icon
- [x] `BookingResource` — nav group 'Bookings', icon OutlinedCalendarDays, sort 1, getRecordTitleAttribute = 'booking_ref', role-based canAccess(), infolist with 5 sections (Booking Details, Passengers, Pricing, Payment, Notes & Audit), full soft-delete scope
- [x] Pushed to GitHub: `9-shen/adventure-balloon`

### Architecture Decisions

- **Price snapshot on creation** — base_adult_price/base_child_price captured at booking time from Product; never recalculated even if product prices change later
- **Filament v4 Get import** — must use `Filament\Schemas\Components\Utilities\Get` (NOT `Filament\Forms\Get`) for reactive form closures
- **All record/toolbar actions** — use `Filament\Actions\*` namespace (EditAction, CreateAction, DeleteAction, etc.) — `Filament\Tables\Actions\*` does NOT exist in Filament v4
- **Wizard on CreateBooking** — `form()` method overridden on the Page class itself (not the Resource), so Edit page sees the flat `BookingEditForm` while Create sees the wizard
- **PAX availability hint** — shown as a `->hint()` on the flight_date field, updated live after date selection; shows ⚠️ prefix if <20 remaining
- **booking_customers key** — table named `booking_customers` (not `customers`) — avoids collision with future CRM customer table

---

## Phase 8 — Partner Booking System

📁 Details: [`docs/phases/phase-08-partner-booking.md`](phases/phase-08-partner-booking.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-07

### Completed ✅

- [x] **BookingService** — `generateRef(string $prefix = 'BLX')` now prefix-agnostic; BLX and PBX maintain independent counters per year
- [x] **BookingService** — `calculatePricing()` extended with optional `?int $partnerId`; private `resolvePrices()` looks up `partner_products` pivot and falls back to base product price
- [x] **BookingService** — `getAvailablePax()` now counts ALL booking types (regular + partner) against the daily cap (removed Phase 8 TODO comment)
- [x] **Booking model** — `partner_id` added to `$fillable`; `partner()` BelongsTo relationship added
- [x] **BookingWizard** — Booking Type radio at top of Step 1 (Regular / Partner) with live reactivity
- [x] **BookingWizard** — Partner `Select` field appears when type = partner (searchable, approved + active partners only)
- [x] **BookingWizard** — Product dropdown filters to partner-assigned products when partner selected; shows all active products for regular bookings
- [x] **BookingWizard** — Step 3 Pricing helpers use `partner_products` pivot prices reactively; `priceSourceInfo()` placeholder shows which price source is active
- [x] **BookingWizard** — Step 5 Review shows booking type and partner name
- [x] **CreateBooking** — `mutateFormDataBeforeCreate()` sets `type`, `partner_id`, calls `generateRef('PBX')` or `generateRef('BLX')` based on booking_type; uses `BookingService::calculatePricing()` with partnerId for snapshot
- [x] **BookingsTable** — `type` badge column (regular = blue, partner = purple)
- [x] **BookingsTable** — `partner.company_name` column (toggleable, placeholder '—' for regulars)
- [x] **BookingsTable** — `SelectFilter` for `type` (Regular / Partner)
- [x] **BookingsTable** — `SelectFilter` for `partner_id` (searchable)
- [x] **BookingEditForm** — Partner Information section (collapsible, read-only) visible only when `type = 'partner'`; shows company_name + type
- [x] **BookingResource infolist** — Type badge added to Booking Details section; Partner Information section visible only when `type = 'partner'`

### Bug Fixes Discovered During Verification 🐛→✅

- [x] **Missing `partner_id` migration** — The original `create_bookings_table` migration was missing the `partner_id` FK column (referenced in blueprint but omitted). Added via `2026_04_07_190035_add_partner_id_to_bookings_table` — nullable FK with `nullOnDelete()`. Caused `SQLSTATE[42S22]: Column not found` on first create attempt.

- [x] **`TextInput::make()->default()` doesn't populate on Edit pages** — The Partner Information section originally used `TextInput::make('partner_name_display')->default(fn($record) => $record->partner->company_name)`. This was blank on the edit page because `->default()` only evaluates for **new records**. Fixed by replacing with `Placeholder::make()->content(fn($record) => ...)` which re-evaluates against the live `$record` on every render.

- [x] **NULL constraint violation (`SQLSTATE[23000]`) on blank optional fields** — When users left `discount_amount`, `amount_paid`, etc. blank in the wizard, they arrived as `null` in `$data`. Even though the DB column has `DEFAULT 0`, an explicit `NULL` in the INSERT fails the NOT NULL constraint. Fixed by explicitly re-assigning all numeric fields with their coalesced values in `mutateFormDataBeforeCreate()` after the pricing calculation block.

### Architecture Decisions

- **Wizard-only field** — `booking_type` radio is unset in `mutateFormDataBeforeCreate()` before DB insert (not a DB column; `type` is the stored field)
- **PBX sequence independence** — `generateRef('PBX')` queries `WHERE booking_ref LIKE 'PBX-YYYY-%'`; completely independent from BLX sequence
- **Partner price snapshot** — `base_adult_price` / `base_child_price` on the booking row store the partner price at creation time, not the base product price, so historical pricing is preserved even if pivot changes
- **Product filter** — uses `whereHas('partners', fn($q) => $q->where('partners.id', $partnerId))` — only shows products with an active pivot row for that partner
- **Edit form read-only display** — Use `Placeholder` (not `TextInput`) for displaying existing record relationship data; `TextInput::default()` is create-only

---

## Phase 9 — Dispatch System

📁 Details: [`docs/phases/phase-09-dispatch.md`](phases/phase-09-dispatch.md)  
**Status: 🔄 IN PROGRESS** — Started 2026-04-08

### Completed ✅

- [x] `dispatches` + `dispatch_drivers` migrations
- [x] `Dispatch` + `DispatchDriver` models with all relationships
- [x] `DispatchService` — `generateRef()`, `suggestDriverAssignments()`, `createDispatch()`
- [x] `DispatchForm::configure()` — CREATE form: reactive booking selector, info card, transport company, status dropdown, driver repeater, notes
- [x] `DispatchForm::forEdit()` — EDIT form: read-only booking block + editable logistics
- [x] **Status management dropdown** — `pending | confirmed | in_progress | delivered | cancelled` on both Create and Edit forms; defaults to `pending`
- [x] `DispatchResource` — modular structure, ViewDispatch/EditDispatch/CreateDispatch pages
- [x] **`DispatchAssignedNotification`** — rich email to transport company: dispatch ref, booking ref, schedule, full passenger list with contacts, driver-vehicle assignments with plates; branded with `AppSettings::company_name`
- [x] **`DriverAssignedNotification`** — email to each driver with assignment details
- [x] **`DispatchService::notifyTransporter()`** — fires email, marks `notified_at`
- [x] **`DispatchService::notifyDrivers()`** — fires driver email notification
- [x] **`DispatchService::sendWhatsAppToDrivers()`** — Twilio WhatsApp API; reads `WhatsAppSettings` from DB; per-driver message with app name, dispatch ref, booking ref, date, pickup time, pickup/dropoff locations, PAX list with contacts, vehicle info; marks `whatsapp_sent` + `whatsapp_sent_at`
- [x] **`twilio/sdk ^8.11.3`** installed
- [x] **`ViewDispatch` page** — "Send WhatsApp to Drivers" green header action button with confirmation modal and smart sent/skipped/error notifications
- [x] **`CreateDispatch::afterCreate()`** — auto-fires `notifyTransporter()` on creation; UI banner shows email confirmation or warning if no email on file
- [x] **Booking Create Wizard** — Booking Status select added to Step 5 (Review & Confirm); defaults to `pending`
- [x] **Booking Edit Form** — Pricing Summary read-only section added; shows adult/child unit price, adult/child totals, discount, final amount (bold) from saved columns
- [x] **Sidebar navigation ordering** — `AdminPanelProvider::navigationGroups()` enforces: Bookings → Transport Management → Partner Management → Product Management → User Management → **Settings (collapsed)**

### Remaining ⏳

- [ ] Driver auto-suggest button — fills repeater automatically from `suggestDriverAssignments()` algorithm
- [ ] `DispatchService::assignDrivers(Dispatch $dispatch)` — write auto-assignments to DB directly

### Architecture Decisions

- **Form split** — `configure()` vs `forEdit()` in `DispatchForm` to avoid Filament OOM from mixing reactive closures with `$record`-bound closures
- **Booking lock** — `->disabled()` + `Hidden::make('booking_id')` ensures FK survives form save
- **Relative `Get` path** — `../../transport_company_id` traverses up from repeater item scope to parent form
- **WhatsApp via Twilio** — `WhatsAppSettings` (Spatie) holds creds; normalises driver phone to `+NNN` format; guarded against disabled/missing config
- **Auto-email on create** — caught in try/catch; errors logged without crashing; UI notification shows result
- **NavigationGroups** — group names must exactly match `getNavigationGroup()` return value in each Resource; `->collapsed()` on Settings hides it by default

---

## Phase 10 — Greeter Module

📁 Details: [`docs/phases/phase-10-greeter.md`](phases/phase-10-greeter.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-08

### Completed ✅

- [x] Greeter navigation group in Admin panel (scoped to `greeter` role)
- [x] Today's Bookings list (filtered by `flight_date = today`)
- [x] Upcoming & next-7-days views
- [x] Per-passenger attendance tracking (`BookingCustomer.attendance`)
- [x] `GreeterCustomersRelationManager` — native Filament table with Show / No-Show / Reset per PAX
- [x] Bulk actions: Mark All Show / Mark All No-Show / Mark Selected
- [x] Auto-sync parent `Booking.attendance` when PAX statuses change
- [x] `ViewGreeterBooking` page with booking summary infolist
- [x] Greeter dashboard widgets (today's stats)

---

## Phase 11 — Accountant Module

📁 Details: [`docs/phases/phase-11-accountant.md`](phases/phase-11-accountant.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-09

### Completed ✅

- [x] `accountant` role added to `RolesAndPermissionsSeeder`
- [x] `User::canAccessPanel()` updated — allows `accountant`, `manager`, `agent`, `dispatcher`, `partner` roles
- [x] `AccountantBookingResource` — Finance Bookings list with:
  - [x] Partner/Type column: shows partner company name OR `🔵 Regular`
  - [x] PAX summary with attendance label per row
  - [x] Financial columns: Final Amount, Amount Paid, Balance Due (color-coded), Payment Status, Method
  - [x] Filters: Payment Status, Payment Method, Outstanding Balance toggle
  - [x] **Process Payment** slide-over action per row — updates `amount_paid`, `payment_method`, `payment_status`, auto-calculates `balance_due`
- [x] `ViewAccountantBooking` — full detail view page with:
  - [x] Booking Details section (ref, type badge, status, PAX attendance)
  - [x] Flight & Partner Information section
  - [x] Passenger Summary section (adults, children, total, source badges)
  - [x] Financial Summary section (amount due, paid, balance, status — all color-coded)
  - [x] Pricing Breakdown section (collapsed by default)
  - [x] **Passenger List & Attendance** table (name, type, phone, nationality, attendance badge)
  - [x] Process Payment header action button
- [x] `AccountantTotalRevenueWidget` — Stats: Total Collected Revenue, Total Outstanding, Pending Invoices count
- [x] `AccountantRecentPaymentsWidget` — Table: last 5 bookings with payments activity

---

---

## Phase 12 — Invoicing System

📁 Details: [`docs/phases/phase-12-invoicing.md`](phases/phase-12-invoicing.md)  
**Status: ✅ COMPLETE** — Completed 2026-04-09

### Completed ✅

#### Database
- [x] `invoices` table — invoice_ref (INV-YYYY-XXXX), partner_id FK, period_from/to, subtotal, tax_rate, tax_amount, total_amount, status ENUM(draft|sent|paid|overdue), sent_at, paid_at, payment_reference, notes, created_by, soft_deletes
- [x] `invoice_items` table — invoice_id FK, booking_id FK, description, flight_date, adult_pax, child_pax, unit_price, line_total
- [x] `add_invoiced_at_to_bookings_table` — adds `invoice_id` FK + `invoiced_at` timestamp to `bookings`

#### Models
- [x] `Invoice` model — `generateRef()`, `isDraft()`, `isSent()`, `isPaid()`, `SoftDeletes`, `hasMany(InvoiceItem)`, `belongsTo(Partner)`, `belongsTo(User, 'created_by')`
- [x] `InvoiceItem` model — `belongsTo(Invoice)`, `belongsTo(Booking)`
- [x] `Partner` model updated — added `invoices()` HasMany, confirmed `bookings()` HasMany
- [x] `Booking` model updated — added `invoice_id`, `invoiced_at` to fillable + `isInvoiced()` helper

#### Service
- [x] `InvoiceService::generate(Partner, bookingIds[], meta)` — creates Invoice + InvoiceItems, stamps `invoiced_at` on each booking to prevent double-invoicing
- [x] `InvoiceService::generatePdf(Invoice)` — DomPDF render of professional invoice blade template
- [x] `InvoiceService::markPaid(Invoice, $reference)` — sets status=paid, paid_at=now(), payment_reference
- [x] `InvoiceService::markSent(Invoice)` — sets status=sent, sent_at=now()

#### PDF Template
- [x] `resources/views/pdf/invoice.blade.php` — A4 professional layout:
  - Header: company name/contact | invoice ref + date | period | status
  - Bill To: partner company name, address, tax number, email
  - Line items table: Date | Ref | Description | Adults | Children | Unit Price | Amount
  - Totals: Subtotal | Tax (if > 0%) | TOTAL DUE (dark accent band)
  - Notes section + payment terms footer

#### Filament Resources (nav group: Invoicing)
- [x] **`PartnerInvoiceResource`** — Partner list (sort 1)
  - Columns: company_name, total_bookings (badge), total_billed, total_paid, total_outstanding (red/green), invoices_count, status badge
  - Filter: partner status
  - Action: "View Bookings" → URL navigate to manage page
- [x] **`ViewPartnerBookings`** (extends `ManageRelatedRecords`, relationship: 'bookings')
  - Table: booking_ref, flight_date, product, PAX, total, paid, balance, payment badge, status badge, invoiced badge
  - **Advanced Date Range filter** on `flight_date` with from/until indicators
  - Filters: payment_status, booking_status, "Not Yet Invoiced" toggle
  - Bulk actions: "Add to Invoice" (skips already-invoiced) / "Remove from Basket"
  - Header: "Create Invoice (N bookings)" slideOver → tax_rate + notes → generates + redirects
  - `public array $selectedForInvoice = []` Livewire property tracks basket state
- [x] **`InvoiceResource`** — all invoices list (sort 2)
  - Columns: invoice_ref, partner, period, items_count, subtotal, total_amount, status badge, created_at, paid_at
  - Filters: status, partner (searchable preload), date range
  - Row actions: View, Download PDF (stream), Mark Sent (confirm), Mark Paid (slideOver with payment_reference)
- [x] **`ViewInvoice`** — full invoice detail page
  - Infolist sections: Invoice Details, Partner/Bill To, Financial Summary, Booking Lines (RepeatableEntry with 7 columns)
  - Header actions: Download PDF, Mark Sent, Mark Paid

#### Bug Fix
- [x] Fixed custom blade view `view-partner-bookings.blade.php` — removed broken `$this->selectedBookingIds` references (old HasTable approach) that caused line 24 IDE CSS parse error and runtime failures. `ManageRelatedRecords` renders the table natively — blade file only needs `<x-filament-panels::page>` wrapper

### Filament v4 Gotchas Discovered in This Phase
- `Filament\Actions\BulkAction` ✅ (`Filament\Tables\Actions\BulkAction` does NOT exist in v4)
- `Filament\Actions\Action` ✅ for table row URL actions
- `ManageRelatedRecords` ✅ for parent→related table drill-down (auto-routes, auto-breadcrumbs, native table)
- `protected static string $view` ❌ on page classes → PHP fatal error. Use `getView()` method or remove entirely
- For `ManageRelatedRecords` pages: custom blade is NOT needed. If it exists (resolved by name convention), keep it minimal — never reference `$this->selectedXxx` properties from the old `HasTable` approach.


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
Phase 4: Product Management ✅
    ↓               ↓
Phase 5: Partners ✅  Phase 6: Transport ✅
    ↓               ↓
Phase 7: Regular Bookings ← NEXT
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

| Item             | Value                       |
| ---------------- | --------------------------- |
| Admin URL        | http://127.0.0.1:8000/admin |
| Admin Email      | webmaster@9-shen.com        |
| DB Name          | booklix                     |
| DB User          | root                        |
| DB Host          | 127.0.0.1:3306 (XAMPP)      |
| Filament Version | v4.0.0                      |
| Laravel Version  | 12.x                        |
| PHP Version      | 8.2.12                      |
