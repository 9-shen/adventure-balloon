# Phase 8 — Partner Booking System

**Status: ⏳ NEXT**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phases 5 (Partner Management), 7 (Regular Booking)  
**Est. Days:** 3–4

---

## Goal

Extend the existing booking system so admins can create bookings **on behalf of partners** using partner-specific pricing. Partner bookings use the same `bookings` table with `type = 'partner'` and generate references in the format `PBX-YYYY-NNNN`.

> **Scope:** Admin panel only (Phase 8). A self-service `/partner` panel is out of scope for Phase 8 and can be added later.

---

## How It Differs from Regular Bookings

| Feature | Regular (Phase 7) | Partner (Phase 8) |
|---------|------------------|--------------------|
| Booking type | `regular` | `partner` |
| Created by | Admin / Manager | Admin (on behalf of partner) |
| Pricing | `products.adult_price` | `partner_products.partner_adult_price` |
| Partner ID | `NULL` | FK to `partners.id` |
| Reference format | `BLX-YYYY-NNNN` | `PBX-YYYY-NNNN` |
| Product selection | All active products | Only products assigned to partner |
| Invoice | On request | Monthly batch (Phase 12) |

---

## Pre-requisites (Already Built ✅)

- `bookings` table has `type` and `partner_id` columns ✅
- `partner_products` pivot has `partner_adult_price` / `partner_child_price` ✅
- `BookingService::generateRef()` just needs a prefix param ✅
- `BookingService::getAvailablePax()` already counts ALL booking types ✅

---

## Files to Modify

```
app/Services/
└── BookingService.php              ← add prefix param + partner price lookup

app/Filament/Admin/Resources/Bookings/
├── Schemas/
│   └── BookingWizard.php           ← add type toggle + partner select + reactive product filter
├── Tables/
│   └── BookingsTable.php           ← add type badge column + partner filter
├── Schemas/
│   └── BookingEditForm.php         ← show partner name (read-only) when type=partner
└── BookingResource.php             ← update infolist with partner section
```

---

## Checklist

### 1. BookingService Updates

- [ ] `generateRef(string $prefix = 'BLX'): string` — parameterise prefix so PBX refs work too
- [ ] `calculatePricing(Product $product, int $adultPax, int $childPax, float $discount = 0, ?int $partnerId = null): array`
    - If `$partnerId` is provided: query `partner_products` pivot for `partner_adult_price` / `partner_child_price`
    - Fallback to `products.adult_price` / `products.child_price` if no partner-specific price found

### 2. BookingWizard.php — Step 1 Changes

- [ ] Add `booking_type` radio/select at the top of Step 1: **Regular** / **Partner**
- [ ] When `booking_type = partner`:
    - [ ] Show `partner_id` Select (searchable, shows partner company name)
    - [ ] Filter product dropdown to only products assigned to that partner (via `partner_products` pivot)
    - [ ] Load partner prices reactively into Step 3 Pricing Placeholders
- [ ] When `booking_type = regular`: hide partner fields (current default behaviour)
- [ ] Step 3 Pricing reactive logic must check `booking_type` and `partner_id` to call correct pricing source

### 3. CreateBooking.php — mutateFormDataBeforeCreate()

- [ ] If `booking_type = partner`: set `type = 'partner'`, `partner_id = $data['partner_id']`
- [ ] If `booking_type = regular`: set `type = 'regular'`, `partner_id = null` (already done)
- [ ] Call `BookingService::generateRef('PBX')` for partner bookings, `generateRef('BLX')` for regular

### 4. BookingsTable.php — Display Updates

- [ ] Add `type` TextColumn with badge: `regular` = blue, `partner` = purple
- [ ] Add `partner.company_name` TextColumn (toggleable)
- [ ] Add `SelectFilter` for `type` (Regular / Partner)
- [ ] Add `SelectFilter` for `partner_id` (searchable partner name)

### 5. BookingEditForm.php

- [ ] Add read-only partner field (shown only when `type = 'partner'`): display `$record->partner->company_name`

### 6. BookingResource.php — Infolist

- [ ] Add **Partner Info** section (visible only when `type = 'partner'`):
    - Partner name (TextEntry)
    - Type badge (TextEntry with badge)

---

## Architecture Notes

- **No new migration needed** — `type`, `partner_id` and `booking_ref` already exist in the `bookings` table
- **PAX cap** — `getAvailablePax()` already sums both types; no change needed
- **Partner price lookup** — use `DB::table('partner_products')->where('partner_id', x)->where('product_id', y)->first()` or the `Partner::products()` relationship with pivot data
- **Reactive product list** — when partner is selected in wizard Step 1, use `->options(fn (Get $get) => Product::whereHas('partners', fn($q) => $q->where('partners.id', $get('partner_id')))->pluck('name', 'id'))` — only active partner products shown
- **PBX sequence** — separate from BLX; `generateRef('PBX')` queries `bookings` where `booking_ref LIKE 'PBX-{year}-%'` for max sequence

---

## Verification Plan

1. Create a partner booking in admin:
   - Select type = Partner → choose a partner → verify only their products appear
   - Select a product → verify Step 3 shows partner price (NOT base product price)
   - Complete wizard → verify ref is `PBX-2026-0001`
2. Go to Bookings list → verify type badge shows "Partner" (purple) and partner name visible
3. Edit the booking → verify partner name shows as read-only info
4. Create a regular booking → verify ref is still `BLX-2026-XXXX` (next in BLX sequence)
5. Check that PAX capacity check counts both BLX and PBX bookings
