# 📘 BOOKLIX — CRM + Booking + Dispatch Platform Blueprint

> **Version:** 1.0 | **Stack:** Laravel 11 · Filament 4 · MySQL · Redis · Twilio  
> **Last Updated:** April 2026

---

## 📌 Table of Contents

1. [Project Overview](#1-project-overview)
2. [Technology Stack](#2-technology-stack)
3. [Architecture Decisions](#3-architecture-decisions)
4. [Role System & Permissions](#4-role-system--permissions)
5. [Database Schema](#5-database-schema)
6. [Phase Breakdown](#6-phase-breakdown)
   - [Phase 1 – Foundation ✅](#phase-1--foundation-)
   - [Phase 2 – Settings & Configuration](#phase-2--settings--configuration)
   - [Phase 3 – User Management](#phase-3--user-management)
   - [Phase 4 – Product Management](#phase-4--product-management)
   - [Phase 5 – Partner Management](#phase-5--partner-management)
   - [Phase 6 – Transport Management](#phase-6--transport-management)
   - [Phase 7 – Regular Booking System](#phase-7--regular-booking-system)
   - [Phase 8 – Partner Booking System](#phase-8--partner-booking-system)
   - [Phase 9 – Dispatch System](#phase-9--dispatch-system)
   - [Phase 10 – Greeter Module](#phase-10--greeter-module)
   - [Phase 11 – Accountant Module](#phase-11--accountant-module)
   - [Phase 12 – Invoicing System](#phase-12--invoicing-system)
   - [Phase 13 – Financial Reports](#phase-13--financial-reports)
   - [Phase 14 – Notifications & Automation](#phase-14--notifications--automation)
   - [Phase 15 – Advanced Features & Polish](#phase-15--advanced-features--polish)
7. [Development Flow](#7-development-flow)
8. [Estimated Timeline](#8-estimated-timeline)

---

## 1. Project Overview

**Booklix** is a full-featured business operations platform for a Hot Air Balloon company (and similar tourism/experience businesses). It covers:

- **CRM**: Customer records, partner accounts, KYC data
- **Booking**: Regular and partner booking workflows with PAX tracking
- **Dispatch**: Driver/vehicle assignment with real-time notifications
- **Finance**: Payment tracking, invoicing, and financial dashboards
- **Operations**: Greeter attendance, accountant verifications

### Core Principles

- All business configuration stored in the **database** (not `.env`)
- **No payment gateway** — payment tracking only (Cash / Wire / Online)
- **250 PAX/day** global capacity limit
- Two booking streams: Regular (admin-created) and Partner (partner-created)
- Role-based dashboards — each role sees only what they need

---

## 2. Technology Stack

| Layer             | Technology                 | Purpose                     |
| ----------------- | -------------------------- | --------------------------- |
| Backend Framework | Laravel 11                 | Core application            |
| Admin Panel       | Filament 4                 | All dashboards & CRUD       |
| Database          | MySQL 8+                   | Primary data store          |
| Cache / Queue     | Redis                      | Sessions, cache, job queues |
| Settings          | Spatie Laravel Settings    | DB-stored config            |
| Roles/Permissions | Spatie Laravel Permission  | RBAC                        |
| Media             | Spatie Media Library       | Logo, product images        |
| Activity Log      | Spatie Activity Log        | Audit trail                 |
| Notifications     | Laravel Notifications      | Email + WhatsApp            |
| WhatsApp          | Twilio API                 | Driver & customer alerts    |
| PDF               | Laravel DomPDF             | Invoice generation          |
| Queue Driver      | Database (→ Redis in prod) | Async notifications         |

### Key Packages

```bash
# Core
composer require filament/filament:"^4.0" -W
composer require spatie/laravel-permission
composer require spatie/laravel-settings
composer require spatie/laravel-medialibrary
composer require spatie/laravel-activitylog

# Utilities
composer require barryvdh/laravel-dompdf
composer require twilio/sdk
composer require maatwebsite/excel          # CSV exports

# Dev
composer require --dev laravel/telescope
```

---

## 3. Architecture Decisions

### 3.1 Configuration: Database vs .env

```
.env  →  Infrastructure ONLY
         APP_KEY, DB_*, REDIS_HOST, QUEUE_DRIVER

Database  →  All Business Settings
             SMTP credentials, Twilio keys,
             Company name/logo, Currency, Timezone
```

**Implementation using Spatie Settings:**

```php
// app/Settings/AppSettings.php
class AppSettings extends Settings {
    public string $company_name;
    public string $company_email;
    public string $company_phone;
    public string $currency;        // EUR
    public string $timezone;        // Europe/Paris
    public string $logo_path;
    public static function group(): string { return 'app'; }
}

// app/Settings/EmailSettings.php
class EmailSettings extends Settings {
    public string $host;
    public int    $port;
    public string $username;
    public string $password;        // encrypted
    public string $encryption;      // tls / ssl
    public string $from_address;
    public string $from_name;
    public static function group(): string { return 'email'; }
}

// app/Settings/WhatsAppSettings.php
class WhatsAppSettings extends Settings {
    public string $account_sid;
    public string $auth_token;      // encrypted
    public string $from_number;     // whatsapp:+14155238886
    public bool   $enabled;
    public static function group(): string { return 'whatsapp'; }
}
```

### 3.2 Pricing Strategy (Adult & Child Split)

```
Product App Prices      →  products.base_adult_price, products.base_child_price
Partner Override Prices →  partner_products.partner_adult_price, partner_products.partner_child_price (pivot)

Example:
  Product: Balloon Classic     App Base: Adult €250, Child €200
  Partner A Override:          Adult €200, Child €150
  Partner B Override:          Adult €220, Child €180

Calculation at booking:
  total = (adult_price × adult_pax) + (child_price × child_pax)
```

### 3.3 Booking Architecture — Unified Stream

We use a **Unified `bookings` table** to ensure instantaneous PAX calculations, simple dispatch logistics, and unified reporting.

| Feature   | Bookings Table Details                                                   |
| --------- | ------------------------------------------------------------------------ |
| Regular   | `type = 'regular'`, `partner_id = NULL`. Uses base app pricing.          |
| Partner   | `type = 'partner'`, `partner_id` populated. Uses partner custom pricing. |
| Customers | Single `booking_customers` table with `pax_type` (adult/child).          |
| Dispatch  | Clean, unified relationship (`dispatches.booking_id`).                   |
| Invoicing | Auto-generated for bookings where `type = partner`.                      |

### 3.4 PAX Inventory — Daily 250 Limit

```php
// Pseudocode: PAX availability check
$date = $booking->flight_date;
$usedPax = Booking::whereDate('flight_date', $date)
                  ->whereIn('status', ['confirmed', 'pending'])
                  ->sum('pax_count')
         + PartnerBooking::whereDate('flight_date', $date)
                          ->whereIn('status', ['confirmed', 'pending'])
                          ->sum('pax_count');

$available = 250 - $usedPax;

if ($newBookingPax > $available) {
    throw new InsufficientCapacityException();
}
```

### 3.5 Dispatch Driver Assignment Logic

```
PAX = 10, Vehicle Capacity = 5
→ Assign 2 drivers (5 PAX + 5 PAX)

PAX = 8, Vehicle Capacity = 5
→ Assign 2 drivers (5 PAX + 3 PAX)

Algorithm:
  drivers_needed = ceil(pax / vehicle_capacity)
  Assign drivers_needed × vehicle records
  Last driver handles remainder PAX
```

### 3.6 Payment Tracking (No Gateway)

```
Payment Status:
  ┌─ Paid      → Full amount received
  ├─ Due       → Nothing received yet
  ├─ Partial   → Deposit paid, balance outstanding
  └─ On-site   → Customer will pay at location

Payment Method:
  ┌─ Cash
  ├─ Wire Transfer
  └─ Online (recorded, not processed)
```

### 3.7 Financial Workflow

```
Booking Created
    │
    ▼
Status: Pending (payment_status: Due)
    │
    ▼ Manager confirms
Status: Confirmed
    │
    ▼ Payment received
payment_status: Paid / Partial
    │
    ▼ Greeter marks attendance
attendance: Show / No-Show
    │
    ▼ Accountant verifies
status: Final
    │
    ▼ (If Partner Booking)
Invoice Generated & Sent
```

---

## 4. Role System & Permissions

### Roles & Dashboard Access

| Role            | Filament Panel        | Key Capabilities               |
| --------------- | --------------------- | ------------------------------ |
| **Super Admin** | Full admin panel      | Everything + Settings          |
| **Admin**       | Full admin panel      | Everything except Settings     |
| **Manager**     | Admin panel           | Bookings, Dispatch, Products   |
| **Accountant**  | Admin panel           | Financials, read-only bookings |
| **Greeter**     | Greeter panel         | Today's bookings, attendance   |
| **Partner**     | Partner panel         | Own bookings, own financials   |
| **Transporter** | Transport panel       | Own drivers, own dispatches    |
| **Driver**      | Driver panel (mobile) | Assigned dispatches only       |

### Permission Matrix

```
Permissions defined via Spatie Permission:

  bookings.view          bookings.create       bookings.edit
  bookings.delete        bookings.confirm      bookings.cancel

  partner_bookings.view  partner_bookings.create

  dispatch.view          dispatch.assign       dispatch.notify

  products.view          products.create       products.edit

  partners.view          partners.create       partners.edit

  transport.view         transport.manage

  invoices.view          invoices.create       invoices.send

  reports.view           reports.export

  settings.view          settings.edit         (Super Admin only)

  users.view             users.create          users.edit

  attendance.manage      (Greeter only)
  payments.manage        (Accountant only)
```

### Filament Panels Structure

```
app/Providers/Filament/
├── AdminPanelProvider.php      → /admin  (Super Admin, Admin, Manager, Accountant)
├── PartnerPanelProvider.php    → /partner
├── TransportPanelProvider.php  → /transport
├── GreeterPanelProvider.php    → /greeter
└── DriverPanelProvider.php     → /driver
```

---

## 5. Database Schema

### 5.1 Core Tables

```sql
-- settings (Spatie managed, key-value)
CREATE TABLE settings (
    id        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group     VARCHAR(255) NOT NULL,
    name      VARCHAR(255) NOT NULL,
    locked    TINYINT(1) NOT NULL DEFAULT 0,
    payload   JSON NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- users
CREATE TABLE users (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255) UNIQUE NOT NULL,
    password        VARCHAR(255) NOT NULL,
    phone           VARCHAR(20),
    avatar_url      VARCHAR(500),
    is_active       TINYINT(1) DEFAULT 1,
    last_login_at   TIMESTAMP NULL,
    email_verified_at TIMESTAMP NULL,
    remember_token  VARCHAR(100),
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL
);
```

### 5.2 Product Tables

```sql
-- products
CREATE TABLE products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    slug            VARCHAR(255) UNIQUE NOT NULL,
    description     TEXT,
    base_adult_price DECIMAL(10,2) NOT NULL,
    base_child_price DECIMAL(10,2) DEFAULT 0,
    duration_minutes INT DEFAULT 60,
    max_pax         INT DEFAULT 250,
    is_active       TINYINT(1) DEFAULT 1,
    sort_order      INT DEFAULT 0,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL
);

-- product_availability (blackout dates / capacity overrides)
CREATE TABLE product_availability (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id      BIGINT UNSIGNED NOT NULL,
    date            DATE NOT NULL,
    max_pax         INT,           -- NULL = use global 250
    is_blocked      TINYINT(1) DEFAULT 0,
    reason          VARCHAR(255),
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id)
);
```

### 5.3 Partner Tables

```sql
-- partners
CREATE TABLE partners (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name        VARCHAR(255) NOT NULL,
    trade_name          VARCHAR(255),
    registration_number VARCHAR(100),
    tax_number          VARCHAR(100),
    email               VARCHAR(255) UNIQUE NOT NULL,
    phone               VARCHAR(20),
    address             TEXT,
    city                VARCHAR(100),
    country             VARCHAR(100),
    bank_name           VARCHAR(255),
    bank_iban           VARCHAR(100),
    bank_swift          VARCHAR(50),
    commission_type     ENUM('fixed','percentage') DEFAULT 'percentage',
    commission_value    DECIMAL(8,2) DEFAULT 0,
    payment_terms_days  INT DEFAULT 30,
    kyc_status          ENUM('pending','approved','rejected') DEFAULT 'pending',
    kyc_documents       JSON,       -- array of document paths
    is_active           TINYINT(1) DEFAULT 1,
    notes               TEXT,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL
);

-- partner_products (custom pricing pivot)
CREATE TABLE partner_products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id      BIGINT UNSIGNED NOT NULL,
    product_id      BIGINT UNSIGNED NOT NULL,
    partner_adult_price DECIMAL(10,2) NOT NULL,
    partner_child_price DECIMAL(10,2) DEFAULT 0,
    is_active       TINYINT(1) DEFAULT 1,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    UNIQUE KEY (partner_id, product_id),
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- partner_users (link user accounts to partner)
CREATE TABLE partner_users (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id  BIGINT UNSIGNED NOT NULL,
    user_id     BIGINT UNSIGNED NOT NULL,
    is_primary  TINYINT(1) DEFAULT 0,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

### 5.4 Booking Tables

```sql
-- bookings (unified for regular and partner)
CREATE TABLE bookings (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_ref     VARCHAR(20) UNIQUE NOT NULL,   -- BLX-2026-0001
    type            ENUM('regular', 'partner') DEFAULT 'regular',
    partner_id      BIGINT UNSIGNED NULL,
    product_id      BIGINT UNSIGNED NOT NULL,
    flight_date     DATE NOT NULL,
    flight_time     TIME,
    adult_pax       INT DEFAULT 0,
    child_pax       INT DEFAULT 0,
    adult_price     DECIMAL(10,2) NOT NULL,
    child_price     DECIMAL(10,2) NOT NULL,
    total_amount    DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    discount_reason VARCHAR(255),
    final_amount    DECIMAL(10,2) NOT NULL,
    payment_method  ENUM('cash','wire','online') NOT NULL,
    payment_status  ENUM('paid','due','partial','on_site') DEFAULT 'due',
    amount_paid     DECIMAL(10,2) DEFAULT 0,
    balance_due     DECIMAL(10,2),
    booking_status  ENUM('pending','confirmed','canceled','completed') DEFAULT 'pending',
    attendance      ENUM('pending','show','no_show') DEFAULT 'pending',
    invoice_id      BIGINT UNSIGNED NULL,
    source          VARCHAR(100),   -- walk-in, phone, email, website
    notes           TEXT,
    created_by      BIGINT UNSIGNED,
    confirmed_by    BIGINT UNSIGNED,
    confirmed_at    TIMESTAMP NULL,
    canceled_by     BIGINT UNSIGNED,
    canceled_at     TIMESTAMP NULL,
    cancellation_reason TEXT,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL,
    FOREIGN KEY (partner_id) REFERENCES partners(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (confirmed_by) REFERENCES users(id)
);

-- booking_customers (one row per PAX)
CREATE TABLE booking_customers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      BIGINT UNSIGNED NOT NULL,
    pax_type        ENUM('adult','child') DEFAULT 'adult',
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    email           VARCHAR(255),
    phone           VARCHAR(20),
    nationality     VARCHAR(100),
    passport_number VARCHAR(50),
    date_of_birth   DATE,
    weight_kg       DECIMAL(5,2),   -- required for balloon safety
    is_primary      TINYINT(1) DEFAULT 0,   -- main contact person
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);
```

### 5.5 Transport Tables

```sql
-- transport_companies
CREATE TABLE transport_companies (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(255) NOT NULL,
    email           VARCHAR(255),
    phone           VARCHAR(20),
    address         TEXT,
    contact_person  VARCHAR(255),
    bank_iban       VARCHAR(100),
    is_active       TINYINT(1) DEFAULT 1,
    notes           TEXT,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL
);

-- vehicles
CREATE TABLE vehicles (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transport_company_id    BIGINT UNSIGNED NOT NULL,
    make                    VARCHAR(100),   -- Toyota, Mercedes
    model                   VARCHAR(100),
    license_plate           VARCHAR(30) UNIQUE NOT NULL,
    capacity                INT NOT NULL,   -- max passengers
    vehicle_type            ENUM('van','bus','minibus','car') DEFAULT 'van',
    price_per_trip          DECIMAL(10,2) DEFAULT 0,
    is_active               TINYINT(1) DEFAULT 1,
    notes                   TEXT,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    FOREIGN KEY (transport_company_id) REFERENCES transport_companies(id)
);

-- drivers
CREATE TABLE drivers (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transport_company_id    BIGINT UNSIGNED NOT NULL,
    first_name              VARCHAR(100) NOT NULL,
    last_name               VARCHAR(100) NOT NULL,
    phone                   VARCHAR(20) NOT NULL,  -- WhatsApp number
    email                   VARCHAR(255),
    license_number          VARCHAR(100),
    license_expiry          DATE,
    is_active               TINYINT(1) DEFAULT 1,
    notes                   TEXT,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    FOREIGN KEY (transport_company_id) REFERENCES transport_companies(id)
);

-- driver_vehicle (assignment)
CREATE TABLE driver_vehicle (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id   BIGINT UNSIGNED NOT NULL,
    vehicle_id  BIGINT UNSIGNED NOT NULL,
    is_default  TINYINT(1) DEFAULT 0,
    assigned_at TIMESTAMP NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);
```

### 5.6 Dispatch Tables

```sql
-- dispatches (one dispatch per booking)
CREATE TABLE dispatches (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispatch_ref            VARCHAR(20) UNIQUE NOT NULL,  -- DSP-2026-0001
    booking_id              BIGINT UNSIGNED NOT NULL,
    transport_company_id    BIGINT UNSIGNED NOT NULL,
    flight_date             DATE NOT NULL,
    pickup_time             TIME NOT NULL,
    pickup_location         VARCHAR(500) NOT NULL,
    dropoff_location        VARCHAR(500),
    total_pax               INT NOT NULL,
    status                  ENUM('pending','confirmed','in_progress','delivered','canceled') DEFAULT 'pending',
    notes                   TEXT,
    notified_at             TIMESTAMP NULL,  -- when transporter was notified
    created_by              BIGINT UNSIGNED,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id),
    FOREIGN KEY (transport_company_id) REFERENCES transport_companies(id)
);

-- dispatch_drivers (one row per assigned driver/vehicle)
CREATE TABLE dispatch_drivers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispatch_id     BIGINT UNSIGNED NOT NULL,
    driver_id       BIGINT UNSIGNED NOT NULL,
    vehicle_id      BIGINT UNSIGNED NOT NULL,
    pax_assigned    INT NOT NULL,   -- how many PAX this driver handles
    status          ENUM('pending','confirmed','in_progress','delivered') DEFAULT 'pending',
    whatsapp_sent   TINYINT(1) DEFAULT 0,
    whatsapp_sent_at TIMESTAMP NULL,
    driver_confirmed_at TIMESTAMP NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (dispatch_id) REFERENCES dispatches(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES drivers(id),
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);
```

### 5.7 Invoice Tables

```sql
-- invoices
CREATE TABLE invoices (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number  VARCHAR(30) UNIQUE NOT NULL,  -- INV-2026-0001
    partner_id      BIGINT UNSIGNED NOT NULL,
    issued_date     DATE NOT NULL,
    due_date        DATE NOT NULL,       -- issued_date + 30 days
    subtotal        DECIMAL(10,2) NOT NULL,
    tax_rate        DECIMAL(5,2) DEFAULT 0,
    tax_amount      DECIMAL(10,2) DEFAULT 0,
    total_amount    DECIMAL(10,2) NOT NULL,
    payment_status  ENUM('unpaid','paid','overdue','partial') DEFAULT 'unpaid',
    amount_paid     DECIMAL(10,2) DEFAULT 0,
    paid_at         TIMESTAMP NULL,
    pdf_path        VARCHAR(500),
    sent_at         TIMESTAMP NULL,
    notes           TEXT,
    created_by      BIGINT UNSIGNED,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (partner_id) REFERENCES partners(id)
);

-- invoice_items (one row per partner booking included)
CREATE TABLE invoice_items (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id          BIGINT UNSIGNED NOT NULL,
    booking_id          BIGINT UNSIGNED NOT NULL,
    description         VARCHAR(500),
    flight_date         DATE,
    pax_count           INT,
    unit_price          DECIMAL(10,2),
    total_price         DECIMAL(10,2),
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
```

---

## 6. Phase Breakdown

---

### Phase 1 — Foundation ✅

**Status:** Complete  
**Deliverables:**

- [x] Laravel 11 installed
- [ ] Filament 4 installed & configured
- [x] MySQL database connected
- [x] Redis configured
- [x] Spatie Permission installed
- [x] Spatie Activity Log installed
- [x] Default Super Admin seeder
- [x] Basic admin panel accessible

---

### Phase 2 — Settings & Configuration

**Priority:** 🔴 HIGH — Foundation for all modules  
**Estimated Time:** 2–3 days

#### What to Build

1. **AppSettings** — Company name, email, phone, address, logo
2. **EmailSettings** — SMTP host, port, credentials, encryption
3. **WhatsAppSettings** — Twilio Account SID, Auth Token, From Number
4. **CurrencySettings** — Default currency, symbol, decimal separator
5. **TimezoneSettings** — App timezone for dates/times

#### Filament Resources

```
app/Filament/Pages/Settings/
├── AppSettingsPage.php         → General company info + logo upload
├── EmailSettingsPage.php       → SMTP configuration + "Send Test Email" action
└── WhatsAppSettingsPage.php    → Twilio config + "Send Test WhatsApp" action
```

#### Key Implementation Details

```php
// Apply SMTP settings at runtime (ServiceProvider or Middleware)
class ApplyEmailSettings {
    public function handle($request, $next) {
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
}
```

#### Migrations Needed

```
database/migrations/
└── 2026_xx_xx_create_settings_table.php   (Spatie auto-migration)
```

#### Seeders

```php
// database/seeders/SettingsSeeder.php
// Seeds default placeholder values for all setting groups
```

---

### Phase 3 — User Management

**Priority:** 🔴 HIGH — Access control foundation  
**Estimated Time:** 2–3 days

#### What to Build

1. **User CRUD** via Filament Resource
2. **Role Assignment** — assign roles from dropdown
3. **User Profile** — KYC fields, avatar, contact info
4. **Permission Management** — view/toggle permissions per user

#### Filament Resources

```
app/Filament/Resources/
└── UserResource.php
    ├── UserResource/Pages/ListUsers.php
    ├── UserResource/Pages/CreateUser.php
    └── UserResource/Pages/EditUser.php
```

#### User Model Fields (KYC Extension)

```php
// Add to users table via migration:
'phone'             => string
'national_id'       => string (nullable)
'nationality'       => string (nullable)
'date_of_birth'     => date (nullable)
'address'           => text (nullable)
'is_active'         => boolean (default: true)
'last_login_at'     => timestamp (nullable)
```

#### Roles to Seed

```php
$roles = [
    'super_admin', 'admin', 'manager',
    'accountant', 'greeter', 'partner',
    'transporter', 'driver'
];
```

---

### Phase 4 — Product Management

**Priority:** 🔴 HIGH — Core business entity  
**Estimated Time:** 3–4 days

#### What to Build

1. **Product CRUD** — name, description, base price, duration
2. **Product Images** — via Spatie Media Library (multiple images)
3. **PAX Capacity** — max_pax per product (default 250 global)
4. **Availability Calendar** — view booked vs available seats per date
5. **Blackout Dates** — block individual dates with reason

#### Filament Resources

```
app/Filament/Resources/
└── ProductResource.php
    ├── Pages/ListProducts.php
    ├── Pages/CreateProduct.php
    ├── Pages/EditProduct.php
    └── Pages/ProductAvailability.php    ← Custom page (calendar view)
```

#### Service Layer

```php
// app/Services/ProductAvailabilityService.php
class ProductAvailabilityService {
    public function getAvailablePax(int $productId, Carbon $date): int;
    public function isDateBlocked(int $productId, Carbon $date): bool;
    public function getMonthlyAvailability(int $productId, Carbon $month): array;
}
```

---

### Phase 5 — Partner Management

**Priority:** 🟠 MEDIUM-HIGH  
**Estimated Time:** 3–4 days  
**Depends On:** Phase 4 (Products)

#### What to Build

1. **Partner Company CRUD** — all company, KYC, banking fields
2. **Partner Custom Pricing** — per-product pricing in pivot table
3. **Partner User Accounts** — link user(s) to partner company
4. **KYC Document Upload** — via Spatie Media Library
5. **Partner Status Management** — pending / approved / rejected

#### Filament Resources

```
app/Filament/Resources/
└── PartnerResource.php
    ├── Pages/ListPartners.php
    ├── Pages/CreatePartner.php
    ├── Pages/EditPartner.php
    └── Pages/PartnerPricing.php      ← Manage custom product prices
```

#### Partner Panel

```
app/Filament/Partner/
├── Pages/PartnerDashboard.php
├── Resources/PartnerBookingResource.php
└── Resources/PartnerInvoiceResource.php
```

---

### Phase 6 — Transport Management

**Priority:** 🟠 MEDIUM-HIGH  
**Estimated Time:** 4–5 days  
**Can run parallel with:** Phase 5

#### What to Build

1. **Transport Company CRUD** — company info, contact, banking
2. **Vehicle Management** — make, model, plate, capacity, type, price
3. **Driver Management** — personal info, license, WhatsApp number
4. **Driver-Vehicle Assignment** — pivot assignment with default flag
5. **Transport User Accounts** — link user to transport company

#### Filament Resources

```
app/Filament/Resources/
├── TransportCompanyResource.php
├── VehicleResource.php
└── DriverResource.php

app/Filament/Transport/       ← Transport partner panel
├── Pages/TransportDashboard.php
└── Resources/DispatchResource.php (read-only view)
```

---

### Phase 7 — Regular Booking System

**Priority:** 🔴 HIGH — Core revenue engine  
**Estimated Time:** 7–10 days  
**Depends On:** Phases 3, 4

#### What to Build

The booking creation follows a **5-step wizard** in Filament.

##### Step 1: Flight Details

- Product selection
- Flight date (with real-time PAX availability check)
- Flight time
- PAX count (Adults, Children)
- Source (walk-in / phone / email / website)

##### Step 2: Customer Details

- Loop: one form per PAX
- Fields: First name, Last name, Email, Phone, Nationality, Passport, DOB, Weight

##### Step 3: Pricing & Discounts

- Auto-calculated totals ((adult_price × adult_pax) + (child_price × child_pax))
- Optional discount (amount or %)
- Discount reason
- Final amount display

##### Step 4: Payment

- Payment method (Cash / Wire / Online)
- Payment status (Paid / Due / Partial / On-site)
- Amount paid (if Partial)
- Balance due (auto-calculated)

##### Step 5: Review & Confirm

- Full booking summary
- Notes field
- Submit → creates booking + customers in transaction

#### Booking Reference Generator

```php
// Format: BLX-2026-0001
// Increment per year, reset each year
public static function generateRef(): string {
    $year = now()->year;
    $last = Booking::whereYear('created_at', $year)->max('id') ?? 0;
    return sprintf('BLX-%d-%04d', $year, $last + 1);
}
```

#### Filament Resources

```
app/Filament/Resources/
└── BookingResource.php
    ├── Pages/ListBookings.php
    ├── Pages/CreateBooking.php    ← 5-step wizard
    ├── Pages/EditBooking.php
    └── Pages/ViewBooking.php     ← Full booking detail + actions
```

#### Booking Actions (Filament Actions)

```
BookingResource actions:
├── ConfirmBooking     → status: pending → confirmed
├── CancelBooking      → status → canceled (with reason modal)
├── UpdatePayment      → adjust payment_status / amount_paid
├── MarkAttendance     → attendance: show / no_show
└── CreateDispatch     → shortcut to dispatch creation
```

#### Service Layer

```php
// app/Services/BookingService.php
class BookingService {
    public function createBooking(array $data): Booking;
    public function confirmBooking(Booking $booking, User $confirmedBy): void;
    public function cancelBooking(Booking $booking, string $reason): void;
    public function calculateTotal(int $productId, int $pax, float $discount): array;
    public function checkAvailability(int $productId, Carbon $date, int $pax): bool;
}
```

#### Form Request Validation

```php
// app/Http/Requests/StoreBookingRequest.php
// Validates: flight_date availability, pax_count > 0,
//            payment amounts consistency, customer data completeness
```

---

### Phase 8 — Partner Booking System

**Priority:** 🟡 MEDIUM  
**Estimated Time:** 3–4 days  
**Depends On:** Phases 5, 7

#### What to Build

Same 5-step wizard as regular bookings, with differences:

| Difference | Regular Booking | Partner Booking |
| ---------- | ------------- | ---------------------------------- |
| Pricing    | App base adult/child prices | Partner override adult/child prices |
| Created by | Admin/Manager | Partner user OR Admin |
| Reference  | BLX prefix | PBX prefix |
| DB Storage | `type = regular` | `type = partner` + `partner_id` |
| Invoice    | On request    | Auto-generated (monthly) |

#### Partner Panel Booking

```
app/Filament/Partner/Resources/
└── BookingResource.php (Filters by authenticated partner_id)
    ├── Pages/ListBookings.php
    ├── Pages/CreateBooking.php
    └── Pages/ViewBooking.php
```

---

### Phase 9 — Dispatch System

**Priority:** 🟠 MEDIUM-HIGH  
**Estimated Time:** 5–7 days  
**Depends On:** Phases 6, 7, 8

#### What to Build

1. **Dispatch Creation** — from confirmed booking
2. **Transport Assignment** — select transport company
3. **Driver Assignment** — auto-suggest based on PAX / vehicle capacity
4. **Notification Sending** — email to transporter, WhatsApp to drivers
5. **Status Tracking** — Pending → Confirmed → In Progress → Delivered

#### Dispatch Dashboard (Filament)

```
app/Filament/Resources/
└── DispatchResource.php
    ├── Pages/ListDispatches.php     ← Table with status filters
    ├── Pages/CreateDispatch.php     ← Select booking → assign transport
    ├── Pages/EditDispatch.php
    └── Pages/ViewDispatch.php       ← Full dispatch + driver statuses
```

#### Driver Assignment Algorithm

```php
// app/Services/DispatchService.php
class DispatchService {
    public function assignDrivers(Dispatch $dispatch): void {
        $pax = $dispatch->total_pax;
        $vehicles = $dispatch->transportCompany->vehicles()
                             ->where('is_active', 1)
                             ->orderBy('capacity', 'desc')
                             ->get();

        $remaining = $pax;
        $assignments = [];

        foreach ($vehicles as $vehicle) {
            if ($remaining <= 0) break;
            $driver = $vehicle->defaultDriver;
            $paxForDriver = min($remaining, $vehicle->capacity);
            $assignments[] = [
                'driver_id'    => $driver->id,
                'vehicle_id'   => $vehicle->id,
                'pax_assigned' => $paxForDriver,
            ];
            $remaining -= $paxForDriver;
        }

        $dispatch->drivers()->createMany($assignments);
    }

    public function notifyTransporter(Dispatch $dispatch): void;
    public function notifyDrivers(Dispatch $dispatch): void;
}
```

#### Notification Templates

```
Transporter Email:
  Subject: Dispatch Assignment #{ref} — {date}
  Body: Full manifest (booking ref, PAX, pickup, dropoff,
        customer names, driver assignments)

Driver WhatsApp:
  "Hello {driver_name}! You have been assigned a pickup.
   Date: {date}, Time: {time}
   Pickup: {location}
   PAX: {count}
   Ref: {dispatch_ref}
   Please confirm receipt."
```

---

### Phase 10 — Greeter Module

**Priority:** 🟡 MEDIUM  
**Estimated Time:** 2–3 days  
**Depends On:** Phase 7, 8

#### What to Build

1. **Today's Bookings View** — all confirmed bookings for today
2. **7-Day Calendar View** — upcoming bookings preview
3. **Attendance Toggling** — mark Show / No-Show per customer
4. **Booking History** — past bookings with search/filter
5. **Greeter Dashboard** — stats: total today, showed, no-showed

#### Greeter Panel

```
app/Filament/Greeter/
├── Pages/GreeterDashboard.php     ← Today stats + quick attendance
├── Pages/TodayBookings.php        ← Full today list
├── Pages/UpcomingBookings.php     ← 7-day view
└── Pages/BookingHistory.php       ← Past bookings
```

#### Key Feature: Attendance Toggle

```php
// Quick toggle action on booking row:
Action::make('markShow')
    ->action(fn(Booking $record) => $record->update(['attendance' => 'show']))
    ->visible(fn(Booking $record) => $record->attendance === 'pending');

Action::make('markNoShow')
    ->action(fn(Booking $record) => $record->update(['attendance' => 'no_show']))
    ->visible(fn(Booking $record) => $record->attendance === 'pending');
```

---

### Phase 11 — Accountant Module

**Priority:** 🔴 HIGH — Financial control  
**Estimated Time:** 3–4 days  
**Depends On:** Phases 7, 8, 10

#### What to Build

1. **Financial Overview** — all bookings with payment status
2. **Payment Adjustment** — update payment status / amount paid
3. **Attendance Verification** — cross-check greeter attendance data
4. **Revenue Summary** — daily/weekly/monthly totals
5. **Due Payments List** — bookings with outstanding balance

#### Accountant Panel Features

```
app/Filament/
└── Resources/
    └── BookingResource.php (accountant view — restricted edit)
        └── Actions:
            ├── UpdatePaymentStatus   → paid/partial/due
            ├── RecordPayment         → add payment received
            └── MarkFinal             → lock booking as finalized
```

#### Financial Dashboard Widgets

```php
// Filament Widgets for Accountant Dashboard:
TotalRevenueWidget        // Total collected this month
OutstandingBalanceWidget  // Total due across all bookings
PaymentsByMethodWidget    // Cash / Wire / Online breakdown
RecentPaymentsWidget      // Last 10 payments recorded
```

---

### Phase 12 — Invoicing System

**Priority:** 🟠 MEDIUM-HIGH  
**Estimated Time:** 4–5 days  
**Depends On:** Phases 5, 8, 11

#### What to Build

1. **Invoice Generation** — from partner bookings (batch by period)
2. **Invoice Line Items** — one line per partner booking
3. **PDF Export** — professional PDF with company branding
4. **Invoice Sending** — email PDF to partner
5. **Payment Tracking** — mark invoice as paid, record payment

#### Invoice Numbering

```
Format: INV-2026-0001
Auto-increment per year
```

#### PDF Template (DomPDF)

```
Invoice Header:
  [Company Logo]    [Company Name/Address]
                    [Invoice Number / Date / Due Date]

Bill To:
  [Partner Company Name]
  [Address / Tax Number]

Line Items Table:
  | Date | Booking Ref | Description | PAX | Unit Price | Total |
  |------|-------------|-------------|-----|-----------|-------|

Summary:
  Subtotal: €XXX
  Tax (if applicable): €X
  Total Due: €XXX

Footer: Payment terms, bank details
```

#### Filament Resource

```
app/Filament/Resources/
└── InvoiceResource.php
    ├── Pages/ListInvoices.php
    ├── Pages/CreateInvoice.php    ← Select partner + date range → auto-fill
    ├── Pages/ViewInvoice.php      ← Preview + Send + Mark Paid actions
    └── Actions/
        ├── GenerateInvoicePdf.php
        └── SendInvoiceEmail.php
```

---

### Phase 13 — Financial Reports & Dashboard

**Priority:** 🟡 MEDIUM  
**Estimated Time:** 4–5 days  
**Depends On:** All booking/financial phases

#### What to Build

1. **Revenue Report** — Regular vs Partner, by date range
2. **Transport Cost Report** — payments to transport companies
3. **Due Payments Report** — outstanding balances
4. **Client Statistics** — repeat customers, nationality breakdown
5. **PAX Statistics** — flight volume, no-show rate
6. **CSV Export** — all reports exportable

#### Dashboard Widgets (Main Admin)

```php
// Filament Dashboard Widgets:
├── StatsOverview
│   ├── Total Bookings This Month
│   ├── Total Revenue This Month
│   ├── Pending Payments
│   └── Today's Flights
├── RevenueChart          // Bar chart: Regular vs Partner (last 12 months)
├── BookingsByStatusChart // Donut chart
├── TopProductsWidget     // Most booked products
└── RecentBookingsTable   // Last 10 bookings
```

#### Reports Structure

```
app/Filament/Pages/Reports/
├── RevenueReport.php
├── TransportReport.php
├── DuePaymentsReport.php
└── ClientStatisticsReport.php
```

---

### Phase 14 — Notifications & Automation

**Priority:** 🟡 MEDIUM  
**Estimated Time:** 3–4 days  
**Depends On:** Phase 2 (Settings), all data phases

#### What to Build

1. **Email Notifications** via Laravel Notifications + DB SMTP
2. **WhatsApp Notifications** via Twilio API
3. **Queue Jobs** for all async notifications
4. **Notification Log** — track sent/failed notifications
5. **Retry Failed Notifications**

#### Notification Classes

```
app/Notifications/
├── BookingConfirmedNotification.php      → Customer email
├── BookingCanceledNotification.php       → Customer email
├── DispatchAssignedNotification.php      → Transporter email
├── DriverAssignedNotification.php        → Driver WhatsApp
├── InvoiceIssuedNotification.php         → Partner email + PDF
└── PaymentReminderNotification.php       → Partner email
```

#### Queue Jobs

```
app/Jobs/
├── SendBookingConfirmation.php
├── SendDispatchNotification.php
├── SendDriverWhatsApp.php
└── SendInvoiceEmail.php
```

#### Twilio WhatsApp Integration

```php
// app/Services/WhatsAppService.php
class WhatsAppService {
    private Twilio $client;

    public function __construct(WhatsAppSettings $settings) {
        $this->client = new Client($settings->account_sid, $settings->auth_token);
    }

    public function send(string $to, string $message): void {
        $this->client->messages->create(
            'whatsapp:' . $to,
            [
                'from' => $settings->from_number,
                'body' => $message,
            ]
        );
    }
}
```

#### Queue Configuration

```php
// .env
QUEUE_CONNECTION=database   // development
QUEUE_CONNECTION=redis      // production

// Run worker:
php artisan queue:work --queue=notifications,default
```

---

### Phase 15 — Advanced Features & Polish

**Priority:** 🟢 LOW  
**Estimated Time:** 3–5 days  
**Depends On:** All previous phases

#### What to Build

1. **Activity Log Viewer** — Spatie log display in Filament
2. **Advanced Search & Filters** — global search across bookings
3. **Bulk Operations** — bulk confirm, bulk cancel, bulk export
4. **Import** — CSV import for bulk bookings (from spreadsheets)
5. **Mobile Responsive** — optimize Filament for mobile greeters/drivers
6. **Dashboard Customization** — widget visibility by role
7. **Audit Trail Reports** — who did what and when

#### Polish Items

```
Performance:
  - Eager loading on all resource queries
  - Index optimization on frequently queried columns
  - Redis caching for settings (already via Spatie)
  - Query result caching for reports

UX:
  - Loading states on heavy operations
  - Confirmation modals for destructive actions
  - Inline editing where appropriate
  - Keyboard shortcuts for power users

Security:
  - Rate limiting on API routes
  - Input sanitization review
  - CSRF everywhere
  - Role/permission checks on all actions
```

---

## 7. Development Flow

```
Phase 1: Foundation ✅
         │
         ▼
Phase 2: Settings & Configuration
         │  (all notifications depend on SMTP/Twilio settings)
         ▼
Phase 3: User Management
         │  (roles needed for all access control)
         ▼
Phase 4: Product Management
         │  (bookings need products)
         ├─────────────────────┐
         ▼                     ▼
Phase 5: Partner Mgmt    Phase 6: Transport Mgmt
         │                     │
         └──────────┬──────────┘
                    ▼
         Phase 7: Regular Bookings
                    │
                    ▼
         Phase 8: Partner Bookings
                    │
                    ▼
         Phase 9: Dispatch System
                    │
                    ├────────────────────────┐
                    ▼                        ▼
         Phase 10: Greeter           Phase 11: Accountant
                    │                        │
                    └────────────┬───────────┘
                                 ▼
                      Phase 12: Invoicing
                                 │
                                 ▼
                      Phase 13: Reports
                                 │
                                 ▼
                      Phase 14: Notifications
                                 │
                                 ▼
                      Phase 15: Polish & Advanced
```

---

## 8. Estimated Timeline

| Phase | Name                 | Complexity | Est. Days        |
| ----- | -------------------- | ---------- | ---------------- |
| 1     | Foundation           | Low        | ✅ Done          |
| 2     | Settings & Config    | Low        | 2–3              |
| 3     | User Management      | Low        | 2–3              |
| 4     | Product Management   | Medium     | 3–4              |
| 5     | Partner Management   | Medium     | 3–4              |
| 6     | Transport Management | Medium     | 4–5              |
| 7     | Regular Booking      | High       | 7–10             |
| 8     | Partner Booking      | Medium     | 3–4              |
| 9     | Dispatch System      | High       | 5–7              |
| 10    | Greeter Module       | Low        | 2–3              |
| 11    | Accountant Module    | Medium     | 3–4              |
| 12    | Invoicing            | Medium     | 4–5              |
| 13    | Financial Reports    | Medium     | 4–5              |
| 14    | Notifications        | Medium     | 3–4              |
| 15    | Polish & Advanced    | Low        | 3–5              |
|       | **TOTAL**            |            | **~53–70 days**  |
|       | **Solo developer**   |            | **~10–14 weeks** |

---

## 📁 Recommended Project Structure

```
app/
├── Filament/
│   ├── Admin/
│   │   ├── Pages/
│   │   │   ├── Dashboard.php
│   │   │   └── Settings/
│   │   └── Resources/
│   │       ├── UserResource.php
│   │       ├── ProductResource.php
│   │       ├── PartnerResource.php
│   │       ├── TransportCompanyResource.php
│   │       ├── DriverResource.php
│   │       ├── VehicleResource.php
│   │       ├── BookingResource.php
│   │       ├── PartnerBookingResource.php
│   │       ├── DispatchResource.php
│   │       └── InvoiceResource.php
│   ├── Greeter/
│   ├── Partner/
│   ├── Transport/
│   └── Driver/
├── Http/
│   ├── Controllers/Api/
│   │   ├── BookingController.php
│   │   └── DispatchController.php
│   └── Requests/
│       ├── StoreBookingRequest.php
│       └── UpdatePaymentRequest.php
├── Models/
│   ├── User.php
│   ├── Product.php
│   ├── ProductAvailability.php
│   ├── Partner.php
│   ├── PartnerProduct.php
│   ├── Booking.php
│   ├── BookingCustomer.php
│   ├── PartnerBooking.php
│   ├── PartnerBookingCustomer.php
│   ├── TransportCompany.php
│   ├── Vehicle.php
│   ├── Driver.php
│   ├── Dispatch.php
│   ├── DispatchDriver.php
│   └── Invoice.php
├── Services/
│   ├── BookingService.php
│   ├── DispatchService.php
│   ├── InvoiceService.php
│   ├── ProductAvailabilityService.php
│   └── WhatsAppService.php
├── Settings/
│   ├── AppSettings.php
│   ├── EmailSettings.php
│   └── WhatsAppSettings.php
├── Notifications/
│   ├── BookingConfirmedNotification.php
│   ├── DispatchAssignedNotification.php
│   └── DriverAssignedNotification.php
└── Jobs/
    ├── SendBookingConfirmation.php
    ├── SendDispatchNotification.php
    └── SendDriverWhatsApp.php

database/
├── migrations/
│   ├── 2026_xx_xx_create_products_table.php
│   ├── 2026_xx_xx_create_partners_table.php
│   ├── 2026_xx_xx_create_partner_products_table.php
│   ├── 2026_xx_xx_create_bookings_table.php
│   ├── 2026_xx_xx_create_booking_customers_table.php
│   ├── 2026_xx_xx_create_partner_bookings_table.php
│   ├── 2026_xx_xx_create_transport_companies_table.php
│   ├── 2026_xx_xx_create_vehicles_table.php
│   ├── 2026_xx_xx_create_drivers_table.php
│   ├── 2026_xx_xx_create_dispatches_table.php
│   └── 2026_xx_xx_create_invoices_table.php
└── seeders/
    ├── DatabaseSeeder.php
    ├── RolesAndPermissionsSeeder.php
    ├── SettingsSeeder.php
    ├── ProductSeeder.php
    └── AdminUserSeeder.php
```

---

## 🚀 Ready to Start Phase 2?

**Next step:** Begin Phase 2 — Settings & Configuration.

### Phase 2 Checklist

- [ ] Run `php artisan settings:discover` after creating Setting classes
- [ ] Create `AppSettings`, `EmailSettings`, `WhatsAppSettings` classes
- [ ] Create migrations via `php artisan vendor:publish --tag="filament-settings-hub-migrations"`
- [ ] Build Filament Settings pages (one per settings group)
- [ ] Add Logo upload field using Spatie Media Library
- [ ] Implement `TestEmailAction` — send test email from SMTP settings page
- [ ] Implement `TestWhatsAppAction` — send test WhatsApp from Twilio settings page
- [ ] Add settings middleware to apply SMTP at runtime
- [ ] Seed default setting values
- [ ] Restrict Settings pages to `super_admin` role only

---

_End of Booklix Platform Blueprint v1.0_
