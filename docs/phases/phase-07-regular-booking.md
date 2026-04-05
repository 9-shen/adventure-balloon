# Phase 7 — Regular Booking System
**Status: 🔲 Pending**  
**Priority:** 🔴 HIGH — Core revenue engine  
**Depends On:** Phases 3, 4  
**Est. Days:** 7–10

---

## Goal
5-step Filament wizard to create a full booking with multiple customers, pricing, discounts, and payment tracking. Stored in unified `bookings` table with `type = 'regular'`.

---

## Booking Reference Format
`BLX-2026-0001` (increment per year, reset January 1st)

---

## Files to Create

```
database/migrations/
├── xxxx_create_bookings_table.php
└── xxxx_create_customers_table.php

app/Models/
├── Booking.php
└── Customer.php

app/Services/
└── BookingService.php

app/Filament/Admin/Resources/
└── BookingResource.php
    └── Pages/
        ├── ListBookings.php
        ├── CreateBooking.php   ← 5-step wizard
        └── ViewBooking.php
```

---

## Checklist

### Database
- [ ] `bookings` table (unified — see schema below)
- [ ] `customers` table (one row per PAX per booking)

### Wizard Steps
- [ ] **Step 1 — Flight Details:** product select, flight date (PAX check on change), time, adult PAX, child PAX, booking source
- [ ] **Step 2 — Customer Details:** dynamic form — one section per PAX (name, email, phone, nationality, passport, DOB, weight)
- [ ] **Step 3 — Pricing & Discounts:** auto-calculate adult total + child total, optional discount, final amount
- [ ] **Step 4 — Payment:** payment method (Cash/Wire/Online), payment status, amount paid, balance due (auto)
- [ ] **Step 5 — Review & Confirm:** full summary, notes field, submit → DB transaction

### Service Layer
- [ ] `BookingService::createBooking(array $data): Booking`
- [ ] `BookingService::confirmBooking(Booking $booking, User $by): void`
- [ ] `BookingService::cancelBooking(Booking $booking, string $reason): void`
- [ ] `BookingService::calculateTotal(int $productId, int $adultPax, int $childPax, float $discount): array`
- [ ] `BookingService::checkAvailability(int $productId, Carbon $date, int $totalPax): bool`

### Booking Resource
- [ ] List page with filters (status, date range, type, source)
- [ ] Status management actions (Confirm, Cancel)
- [ ] View page with all booking + customer details

---

## Key Schema

```sql
CREATE TABLE bookings (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_ref       VARCHAR(20)  NOT NULL UNIQUE,   -- BLX-2026-0001
    type              ENUM('regular','partner') NOT NULL DEFAULT 'regular',
    partner_id        BIGINT UNSIGNED NULL,            -- NULL for regular
    product_id        BIGINT UNSIGNED NOT NULL,
    flight_date       DATE         NOT NULL,
    flight_time       TIME         NULL,
    adult_pax         INT          NOT NULL DEFAULT 1,
    child_pax         INT          NOT NULL DEFAULT 0,
    booking_source    VARCHAR(100) NULL,              -- walk-in, phone, website, etc.
    base_adult_price  DECIMAL(10,2) NOT NULL,
    base_child_price  DECIMAL(10,2) NOT NULL DEFAULT 0,
    adult_total       DECIMAL(10,2) NOT NULL,
    child_total       DECIMAL(10,2) NOT NULL DEFAULT 0,
    discount_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    final_amount      DECIMAL(10,2) NOT NULL,
    payment_method    ENUM('cash','wire','online') NOT NULL DEFAULT 'cash',
    payment_status    ENUM('due','partial','paid','on_site') NOT NULL DEFAULT 'due',
    amount_paid       DECIMAL(10,2) NOT NULL DEFAULT 0,
    balance_due       DECIMAL(10,2) NOT NULL DEFAULT 0,
    booking_status    ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    cancelled_reason  TEXT         NULL,
    notes             TEXT         NULL,
    confirmed_by      BIGINT UNSIGNED NULL,
    confirmed_at      TIMESTAMP    NULL,
    created_by        BIGINT UNSIGNED NULL,
    created_at        TIMESTAMP    NULL,
    updated_at        TIMESTAMP    NULL,
    FOREIGN KEY (partner_id)   REFERENCES partners(id),
    FOREIGN KEY (product_id)   REFERENCES products(id),
    FOREIGN KEY (confirmed_by) REFERENCES users(id),
    FOREIGN KEY (created_by)   REFERENCES users(id)
);

CREATE TABLE customers (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id      BIGINT UNSIGNED NOT NULL,
    type            ENUM('adult','child') NOT NULL DEFAULT 'adult',
    full_name       VARCHAR(255) NOT NULL,
    email           VARCHAR(255) NULL,
    phone           VARCHAR(50)  NULL,
    nationality     VARCHAR(100) NULL,
    passport_number VARCHAR(100) NULL,
    date_of_birth   DATE         NULL,
    weight_kg       DECIMAL(5,2) NULL,
    attendance      ENUM('pending','show','no_show') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);
```

---

## Notes
- All booking creation happens inside a **DB transaction** — if customer insert fails, booking rolls back
- PAX check at Step 1: `(used_pax + new_pax) <= 250`
- `balance_due = final_amount - amount_paid` (computed on save)
