# Phase 4 — Product Management
**Status: 🔲 Pending**  
**Priority:** 🔴 HIGH — Core business entity  
**Depends On:** Phase 1 ✅  
**Est. Days:** 3–4

---

## Goal
Manage balloon flight products with adult/child pricing, capacity limits, multiple images, and an availability calendar with blackout dates.

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

app/Filament/Admin/Resources/
└── ProductResource.php
    └── Pages/
        ├── ListProducts.php
        ├── CreateProduct.php
        └── EditProduct.php
```

---

## Checklist

### Database
- [ ] `products` table migration
- [ ] `blackout_dates` table migration

### Models
- [ ] `Product` model with Spatie Media Library (`HasMedia`)
- [ ] `BlackoutDate` model

### Service Layer
- [ ] `ProductAvailabilityService::getAvailablePax(int $productId, Carbon $date): int`
- [ ] `ProductAvailabilityService::isDateBlocked(int $productId, Carbon $date): bool`
- [ ] `ProductAvailabilityService::getMonthlyAvailability(int $productId, Carbon $month): array`

### Filament Resource
- [ ] `ProductResource` CRUD
- [ ] Multiple images upload (Spatie Media Library)
- [ ] Availability calendar widget/view
- [ ] Blackout date management (inline or separate resource)
- [ ] Adult & child price fields clearly separated

---

## Key Schema

```sql
CREATE TABLE products (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(255)   NOT NULL,
    description         TEXT           NULL,
    base_adult_price    DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    base_child_price    DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    max_pax             INT            NOT NULL DEFAULT 250,
    duration_minutes    INT            NOT NULL DEFAULT 60,
    is_active           BOOLEAN        NOT NULL DEFAULT 1,
    created_at          TIMESTAMP      NULL,
    updated_at          TIMESTAMP      NULL
);

CREATE TABLE blackout_dates (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id  BIGINT UNSIGNED NULL,   -- NULL = global blackout
    date        DATE NOT NULL,
    reason      VARCHAR(255) NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

---

## Notes
- `max_pax = 250` is the global daily cap (from Core Rules)
- Product images stored as Spatie media collection `'product-images'`
- Blackout date with `product_id = NULL` blocks ALL products on that day
