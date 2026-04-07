# Phase 7 — Regular Booking System

**Status: ✅ COMPLETE** — Completed 2026-04-07  
**Priority:** 🔴 HIGH — Core revenue engine  
**Depends On:** Phases 3, 4  
**Est. Days:** 7–10 | **Actual:** 1 day

---

## Goal

5-step Filament wizard to create a full booking with multiple passengers, pricing snapshots, discounts, and payment tracking. Stored in unified `bookings` table with `type = 'regular'`.

---

## Booking Reference Format

`BLX-2026-0001` — sequential per year, resets January 1st. Collision-safe (lock-based in BookingService).

---

## Files Created

```
database/migrations/
├── 2026_04_07_121651_create_bookings_table.php
└── 2026_04_07_121656_create_booking_customers_table.php

app/Models/
├── Booking.php
└── BookingCustomer.php

app/Services/
└── BookingService.php

app/Filament/Admin/Resources/Bookings/
├── BookingResource.php
├── Schemas/
│   ├── BookingWizard.php      ← 5-step creation wizard
│   └── BookingEditForm.php    ← flat edit form
├── Tables/
│   └── BookingsTable.php
├── Pages/
│   ├── ListBookings.php
│   ├── CreateBooking.php      ← wizard host, PAX check, ref gen
│   ├── EditBooking.php        ← Confirm/Cancel actions
│   └── ViewBooking.php
└── RelationManagers/
    └── BookingCustomersRelationManager.php
```

---

## Checklist — All Complete ✅

### Database

- [x] `bookings` table — booking_ref (BLX-YYYY-NNNN unique), type enum (regular/partner), partner_id (nullable FK), product_id FK, flight_date, flight_time (nullable), adult_pax, child_pax, booking_source, base_adult_price, base_child_price (snapshot), adult_total, child_total, discount_amount, discount_reason, final_amount, payment_method (cash/wire/online), payment_status (due/partial/paid/on_site), amount_paid, balance_due, booking_status (pending/confirmed/cancelled/completed), cancelled_reason, notes, created_by FK, confirmed_by FK, confirmed_at, cancelled_by FK, cancelled_at, SoftDeletes
- [x] `booking_customers` table — booking_id FK (CASCADE DELETE), type (adult/child), full_name, email (nullable), phone (nullable), nationality (nullable), passport_number (nullable), date_of_birth (nullable), weight_kg (nullable), is_primary (bool, default false)

### Models

- [x] `Booking` — SoftDeletes, fillable, casts (decimal, date, timestamps), relationships (product, createdBy, confirmedBy, cancelledBy, customers hasMany BookingCustomer), helper methods: `getTotalPax()`, `isPending()`, `isConfirmed()`, `isCancelled()`, `isCompleted()`, `getStatusColor()`, `getPaymentStatusColor()`
- [x] `BookingCustomer` — fillable, date/decimal casts, `belongsTo(Booking)`

### Service Layer

- [x] `BookingService::generateRef()` — `BLX-{year}-{padded}` sequential per year
- [x] `BookingService::getAvailablePax(Carbon $date)` — 250 minus sum of pending+confirmed adult_pax+child_pax on that date
- [x] `BookingService::checkAvailability(Carbon $date, int $pax)` — bool
- [x] `BookingService::calculatePricing(Product, int $adultPax, int $childPax, float $discount)` — returns array with price snapshot + totals
- [x] `BookingService::createBooking(array $data)` — DB transaction: creates Booking + loops `passengers` array to create BookingCustomer rows

### Wizard (BookingWizard.php)

- [x] **Step 1 — Flight Details**: product Select (active only), flight_date DatePicker with live PAX hint (⚠️ if <20 remaining), flight_time TimePicker (nullable), adult_pax/child_pax NumberInputs (live), booking_source Select (Walk-in / Phone / Website / WhatsApp / Agency / Other)
- [x] **Step 2 — Customer Details**: info Placeholder showing expected PAX count, Repeater (label shown on collapse from `full_name`): type Select (adult/child, default adult), full_name TextInput (required), email, phone, nationality, passport_number, date_of_birth DatePicker, weight_kg NumericInput, is_primary Toggle
- [x] **Step 3 — Pricing & Discounts**: read-only Placeholders for Adult Total, Child Total, Final Amount (all reactive via `Get`), discount_amount NumericInput, discount_reason TextInput
- [x] **Step 4 — Payment**: payment_method Select (Cash/Wire/Online), payment_status Select (Due/Partial/Paid/On Site), amount_paid NumericInput, live balance_due Placeholder
- [x] **Step 5 — Review & Confirm**: read-only summary Placeholders (product, date, adults, children, source, method, final amount), notes Textarea

### Pages

- [x] **CreateBooking** — `form()` injects `BookingWizard`, `beforeCreate()` checks PAX capacity (halt if exceeded), `mutateFormDataBeforeCreate()` generates ref, computes all calculated fields, sets type='regular', created_by, `handleRecordCreation()` delegates to `BookingService::createBooking()`, `afterCreate()` shows success notification with ref
- [x] **EditBooking** — Confirm Booking header action (visible when isPending()), Cancel Booking header action (modal form with cancelled_reason TextArea), DeleteAction; `mutateFormDataBeforeSave()` recalculates balance_due from final_amount - amount_paid
- [x] **ViewBooking** — standard infolist view, Edit button in header
- [x] **ListBookings** — table with New Booking header action

### RelationManager

- [x] **BookingCustomersRelationManager** — inline passenger CRUD on EditBooking page; columns: full_name (bold, searchable), type (badge: adult=info, child=warning), email, phone, nationality, weight (toggleable), is_primary (boolean icon); form modal: all fields; record actions: EditAction, DeleteAction; toolbar: CreateAction('Add Passenger'), BulkActionGroup(DeleteBulkAction)

### BookingResource (Infolist)

- [x] Infolist with 5 sections: **Booking Details** (ref, product, status badge, flight date/time, source), **Passengers** (adults, children, total PAX), **Pricing** (adult price each, child price each, adult total, child total, discount, final amount), **Payment** (method badge, payment status badge, amount paid, balance due), **Notes & Audit** (internal notes, cancellation reason, booking ref, created by, created at)

---

## Architecture Decisions

| Decision | Rationale |
|----------|-----------|
| **Price snapshot** | `base_adult_price` / `base_child_price` captured at create from Product — historical accuracy if product prices change |
| **`booking_customers` table** | Named specifically to avoid collision with future CRM `customers` table |
| **Wizard on Page, not Resource** | `CreateBooking::form()` overrides to return wizard; `BookingResource::form()` returns flat `BookingEditForm`. Edit page gets the simple form automatically |
| **`Filament\Schemas\Components\Utilities\Get`** | Filament v4 moved `Get`/`Set` out of `Filament\Forms` — must use the new namespace for reactive closures |
| **`Filament\Actions\*`** | All record actions (EditAction, DeleteAction, CreateAction, ViewAction) and bulk actions come from `Filament\Actions` in v4, NOT `Filament\Tables\Actions` |
| **PAX check in `beforeCreate()`** | Halts the Filament create process before DB write; shows a danger notification and stops wizard submission |
| **`handleRecordCreation()`** | Delegates to `BookingService::createBooking()` which runs the DB transaction; returns the `Booking` model so Filament can redirect to view |

---

## Key Schema

```sql
CREATE TABLE bookings (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_ref         VARCHAR(20)  NOT NULL UNIQUE,   -- BLX-2026-0001
    type                ENUM('regular','partner') NOT NULL DEFAULT 'regular',
    partner_id          BIGINT UNSIGNED NULL,
    product_id          BIGINT UNSIGNED NOT NULL,
    flight_date         DATE         NOT NULL,
    flight_time         TIME         NULL,
    adult_pax           INT          NOT NULL DEFAULT 1,
    child_pax           INT          NOT NULL DEFAULT 0,
    booking_source      VARCHAR(100) NULL,
    base_adult_price    DECIMAL(10,2) NOT NULL,
    base_child_price    DECIMAL(10,2) NOT NULL DEFAULT 0,
    adult_total         DECIMAL(10,2) NOT NULL,
    child_total         DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount     DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_reason     VARCHAR(255) NULL,
    final_amount        DECIMAL(10,2) NOT NULL,
    payment_method      ENUM('cash','wire','online') NOT NULL DEFAULT 'cash',
    payment_status      ENUM('due','partial','paid','on_site') NOT NULL DEFAULT 'due',
    amount_paid         DECIMAL(10,2) NOT NULL DEFAULT 0,
    balance_due         DECIMAL(10,2) NOT NULL DEFAULT 0,
    booking_status      ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    cancelled_reason    TEXT NULL,
    notes               TEXT NULL,
    created_by          BIGINT UNSIGNED NULL,
    confirmed_by        BIGINT UNSIGNED NULL,
    confirmed_at        TIMESTAMP NULL,
    cancelled_by        BIGINT UNSIGNED NULL,
    cancelled_at        TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL
);

CREATE TABLE booking_customers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      BIGINT UNSIGNED NOT NULL,
    type            ENUM('adult','child') NOT NULL DEFAULT 'adult',
    full_name       VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NULL,
    phone           VARCHAR(50)  NULL,
    nationality     VARCHAR(100) NULL,
    passport_number VARCHAR(100) NULL,
    date_of_birth   DATE NULL,
    weight_kg       DECIMAL(5,2) NULL,
    is_primary      BOOLEAN NOT NULL DEFAULT 0,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);
```
