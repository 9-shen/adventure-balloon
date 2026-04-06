# Phase 4 — Product Management
**Status: ⏳ NEXT**
**Priority:** 🔴 HIGH — Core business entity
**Depends On:** Phase 1 ✅, Phase 2 ✅
**Est. Days:** 3–4

---

## Goal
Manage hot air balloon flight products with adult/child base pricing, optional duration, multiple images, and blackout date management.
Partner-specific pricing (override per product per partner) is handled in **Phase 5** via the `partner_products` pivot.

---

## ⚠️ Architecture Decisions

### 1. NO `max_pax` on Products
Daily PAX capacity is a **global business setting**, not a per-product field.
It already lives in `PaxSettings::daily_pax_capacity` (Phase 2 ✅).
All availability checks read from there — never from the product.

### 2. Availability Calculation is Split
- **Phase 4** → Blackout date blocking only (`isDateBlocked`)
- **Phase 7** → Full availability check: used PAX from bookings + blackout dates combined

### 3. Soft Deletes on Everything
All models use `SoftDeletes`. ProductResource follows the same pattern as UserResource (Phase 3).

### 4. Partner Pricing is Phase 5
Products only store **base prices** (`base_adult_price`, `base_child_price`).
Partner overrides live in `partner_products` pivot — built in Phase 5.

---

## Files to Create

```
database/migrations/
├── xxxx_create_products_table.php
└── xxxx_create_blackout_dates_table.php

app/Models/
├── Product.php
└── BlackoutDate.php

app/Services/
└── ProductAvailabilityService.php

app/Filament/Admin/Resources/Products/
├── ProductResource.php
├── Pages/
│   ├── ListProducts.php
│   ├── CreateProduct.php
│   ├── EditProduct.php
│   └── ViewProduct.php
├── Schemas/
│   ├── ProductForm.php
│   └── ProductInfolist.php
├── Tables/
│   └── ProductsTable.php
└── RelationManagers/
    └── BlackoutDatesRelationManager.php
```

---

## Key Schema

```sql
CREATE TABLE products (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(255)   NOT NULL,
    description         TEXT           NULL,
    base_adult_price    DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    base_child_price    DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    duration_minutes    INT            NULL,          -- nullable, informational only
    is_active           BOOLEAN        NOT NULL DEFAULT 1,
    deleted_at          TIMESTAMP      NULL,          -- soft deletes
    created_at          TIMESTAMP      NULL,
    updated_at          TIMESTAMP      NULL
);

-- NO max_pax column — capacity is global via PaxSettings::daily_pax_capacity

CREATE TABLE blackout_dates (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  BIGINT UNSIGNED NULL,   -- NULL = blocks ALL products on that day
    date        DATE NOT NULL,
    reason      VARCHAR(255) NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

---

## Checklist

### Database
- [ ] `products` table migration (no `max_pax`, include `deleted_at`)
- [ ] `blackout_dates` table migration

### Models
- [ ] `Product` model
  - `HasMedia`, `InteractsWithMedia` — media collection `'product-images'`
  - `SoftDeletes`
  - `hasMany(BlackoutDate)`
  - `belongsToMany(Partner)` via `partner_products` (pivot defined in Phase 5)
- [ ] `BlackoutDate` model
  - `belongsTo(Product)` (nullable)

### Service Layer (Phase 4 scope only)
- [ ] `ProductAvailabilityService::isDateBlocked(int|null $productId, Carbon $date): bool`
  - Returns `true` if there is a blackout_date for this product on this date
  - Also returns `true` if there is a global blackout (`product_id = NULL`) for this date

### Filament Resource (follows Phase 3 patterns)
- [ ] `ProductResource` — navigation group "Product Management", restricted to `super_admin` + `admin` + `manager`
- [ ] Soft delete support: `getEloquentQuery()` without `SoftDeletingScope`, `canDelete()`, `canForceDelete()`
- [ ] **ProductForm** (Schema)
  - Section: Basic Info (name, description, is_active toggle)
  - Section: Pricing (base_adult_price, base_child_price — clearly labelled, side by side)
  - Section: Details (duration_minutes — nullable, optional)
  - Section: Images (SpatieMediaLibraryFileUpload — multiple, collection `'product-images'`)
- [ ] **ProductsTable**
  - Columns: thumbnail image, name, adult price, child price, active toggle, created_at
  - Actions: View, Edit, Delete, Restore, ForceDelete
  - Filter: TrashedFilter
- [ ] **ProductInfolist** (View page)
  - Sections: Basic Info, Pricing, Details, System
- [ ] **BlackoutDatesRelationManager** (inline inside ProductResource)
  - Table: date, reason, scope (global vs product-specific)
  - Create/Edit/Delete inline

---

## Pricing Flow Summary

```
Product.base_adult_price   ← Used for all REGULAR bookings
Product.base_child_price   ← Used for all REGULAR bookings

partner_products.partner_adult_price  ← Overrides for PARTNER bookings (Phase 5)
partner_products.partner_child_price  ← Overrides for PARTNER bookings (Phase 5)

Booking calculation (Phase 7):
  total = (adult_price × adult_pax) + (child_price × child_pax) - discount
  adult_price = partner override if exists, else product base
  child_price = partner override if exists, else product base
```

---

## Global Availability Check Flow (to be implemented in Phase 7)

```
User selects date →
  1. isDateBlocked($productId, $date) → if true: reject
  2. usedPax = Booking::whereDate('flight_date', $date)
                        ->whereIn('booking_status', ['confirmed','pending'])
                        ->sum(adult_pax + child_pax)
  3. available = PaxSettings::daily_pax_capacity - usedPax
  4. if (requested_pax > available) → reject
```

---

## Notes
- Product images stored as Spatie media collection `'product-images'`
- `duration_minutes` is informational — may appear on PDFs and partner portal in future
- Blackout date with `product_id = NULL` blocks ALL products on that day (global holiday/maintenance)
- Soft-deleted products must NOT appear in booking wizard product selector
