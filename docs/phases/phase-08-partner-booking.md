# Phase 8 — Partner Booking System

**Status: ✅ COMPLETE**
**Completed:** 2026-04-07
**Priority:** 🟡 MEDIUM
**Depends On:** Phases 5 (Partner Management), 7 (Regular Booking)

---

## Goal

Extend the existing booking system so admins can create bookings **on behalf of partners** using partner-specific pricing. Partner bookings use the same `bookings` table with `type = 'partner'` and generate references in the format `PBX-YYYY-NNNN`.

> **Scope:** Admin panel only (Phase 8). A self-service `/partner` panel is out of scope for Phase 8 and can be added later.

---

## How It Differs from Regular Bookings

| Feature | Regular (Phase 7) | Partner (Phase 8) |
|---------|------------------|---------------------|
| Booking type | `regular` | `partner` |
| Created by | Admin / Manager | Admin (on behalf of partner) |
| Pricing | `products.base_adult_price` | `partner_products.partner_adult_price` |
| Partner ID | `NULL` | FK to `partners.id` |
| Reference format | `BLX-YYYY-NNNN` | `PBX-YYYY-NNNN` |
| Product selection | All active products | Only products assigned to partner |
| Invoice | On request | Monthly batch (Phase 12) |

---

## Completed Checklist ✅

### 1. Migration
- [x] `2026_04_07_190035_add_partner_id_to_bookings_table` — adds `partner_id` nullable FK to `partners` table
  > **Note:** The original `create_bookings_table` migration was missing `partner_id`. It was added via an additive migration, not by modifying the original.

### 2. BookingService.php
- [x] `generateRef(string $prefix = 'BLX'): string` — prefix-agnostic; BLX and PBX maintain **independent counters per year** (`WHERE booking_ref LIKE '{PREFIX}-{YEAR}-%'`)
- [x] `calculatePricing(Product $product, int $adultPax, int $childPax, float $discount = 0, ?int $partnerId = null): array`
  - Looks up `partner_products` pivot for partner-specific prices when `$partnerId` is set
  - Falls back to `products.base_adult_price` / `products.base_child_price` if no pivot row found
  - Private `resolvePrices()` method handles the DB lookup cleanly
- [x] `getAvailablePax()` — already counts ALL booking types (no change needed in Phase 8)

### 3. Booking Model
- [x] `partner_id` added to `$fillable`
- [x] `partner()` BelongsTo relationship added (→ `Partner` model)

### 4. BookingWizard.php — Step 1 Changes
- [x] `booking_type` ToggleButton radio at top: **✈️ Regular Booking** / **🤝 Partner Booking**
- [x] `partner_id` searchable Select — appears only when `booking_type = 'partner'`; filters to `approved` + `active` partners
- [x] Product dropdown reacts to `partner_id`: shows only partner-assigned products; falls back to all active when no partner selected
- [x] Step 3 Pricing — `priceSourceInfo()` placeholder shows `✅ Partner prices applied — Adult: MAD X | Child: MAD Y` vs base price info reactively
- [x] Step 5 Review — shows Booking Type and Partner Name

### 5. CreateBooking.php — mutateFormDataBeforeCreate()
- [x] Sets `type = 'partner'` / `type = 'regular'` based on `booking_type` radio
- [x] Sets `partner_id` (null for regular)
- [x] Calls `generateRef('PBX')` or `generateRef('BLX')` accordingly
- [x] Calls `calculatePricing()` with `$partnerId` for partner-specific price snapshot
- [x] **Bug fix:** Explicit null-safe defaults for all NOT NULL columns before INSERT:
  ```php
  $data['discount_amount']  = $discount;       // prevents SQLSTATE[23000] when left blank
  $data['amount_paid']      = $amountPaid;
  $data['adult_pax']        = $adultPax;
  $data['child_pax']        = $childPax;
  $data['flight_time']      = $data['flight_time'] ?: null;
  $data['booking_source']   = $data['booking_source'] ?: null;
  $data['discount_reason']  = $data['discount_reason'] ?? null;
  $data['notes']            = $data['notes'] ?? null;
  $data['cancelled_reason'] = $data['cancelled_reason'] ?? null;
  ```
- [x] Removes `booking_type` from `$data` before DB insert (wizard-only field, no DB column)

### 6. BookingsTable.php
- [x] `type` badge column — `regular` = blue (`info`), `partner` = purple
- [x] `partner.company_name` column (toggleable, placeholder `—` for regular bookings)
- [x] `SelectFilter` for `type` (Regular / Partner)
- [x] `SelectFilter` for `partner_id` (searchable by partner name)

### 7. BookingEditForm.php
- [x] **Partner Information** section — collapsible, visible only when `type = 'partner'`
- [x] Uses `Placeholder` components (NOT `TextInput`) to display partner name and type badge
  > **Critical gotcha:** `TextInput::make()->default(fn($record) => ...)` does NOT populate on edit pages — `->default()` only runs on create. Use `Placeholder::make()->content(fn($record) => ...)` for read-only record-bound values in edit forms.

### 8. BookingResource.php — Infolist
- [x] `type` badge entry added to **Booking Details** section (regular=blue, partner=purple)
- [x] **Partner Information** section — columns(2), visible only when `type = 'partner'`
  - `partner.company_name` TextEntry
  - `type` badge TextEntry with 🤝 prefix

---

## Architecture Notes

- **Single `bookings` table** — `type` column distinguishes regular vs partner; `partner_id` nullable (NULL for regular)
- **Partner price snapshot** — `base_adult_price` / `base_child_price` on the booking row store the resolved price at creation time; historical pricing preserved if pivot prices change later
- **PBX sequence independence** — `generateRef('PBX')` queries `WHERE booking_ref LIKE 'PBX-YYYY-%'`; completely independent from BLX sequence
- **Wizard-only field** — `booking_type` radio is removed from `$data` in `mutateFormDataBeforeCreate()` before DB insert; `type` is the column that's stored
- **Product filter** — uses `whereHas('partners', fn($q) => $q->where('partners.id', $partnerId))` — only shows products with an active pivot row for that partner

---

## Known Gotchas (Learned in Phase 8)

1. **Missing migration** — `partner_id` was referenced in the blueprint but not in `create_bookings_table`. Added via separate migration. **Always verify schema matches model `$fillable` before building forms.**

2. **`TextInput::make()->default()` doesn't populate on Edit** — `->default()` is only evaluated for new records. For read-only display of existing record data in edit forms, use `Placeholder::make()->content(fn($record) => ...)`.

3. **Explicit NULL breaks NOT NULL columns** — Even if a DB column has `DEFAULT 0`, an explicit `NULL` passed in the INSERT array will throw `SQLSTATE[23000]`. Always cast optional form fields to their default values in `mutateFormDataBeforeCreate()`.

---

## Verification Results (2026-04-07)

| Test | Result |
|------|--------|
| Booking type radio visible in wizard | ✅ |
| Partner select appears for partner type | ✅ |
| Product dropdown filtered to partner's products | ✅ |
| Partner prices shown in Step 3 pricing | ✅ (MAD 900 adult, MAD 700 child) |
| PBX-2026-0001 generated on first partner booking | ✅ |
| PBX-2026-0002 generated with blank optional fields (no crash) | ✅ |
| Regular BLX sequence unaffected | ✅ |
| Type badge shows "Partner" in purple in list | ✅ |
| Partner column shows company name / — for regulars | ✅ |
| Edit form shows partner name and type badge | ✅ (after Placeholder fix) |
| View page shows Partner Information section | ✅ |
