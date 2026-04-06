# Phase 5 — Partner Management
**Status: ✅ COMPLETE** — Completed 2026-04-06
**Priority:** 🟠 MEDIUM-HIGH
**Depends On:** Phase 4
**Est. Days:** 3–4

---

## Goal
Manage partner companies with custom adult/child pricing per product, KYC documents, and approval workflow.

---

## Completed ✅

### Database
- [x] `partners` table — company info, banking, status workflow, soft deletes
- [x] `partner_products` pivot table — custom adult/child pricing per partner per product

### Models
- [x] `Partner` model — `HasMedia`, `SoftDeletes`, `belongsToMany(Product)` via `PartnerProduct`
- [x] `PartnerProduct` — Pivot model with `partner_adult_price`, `partner_child_price`, `is_active`
- [x] `Product` model updated — `partners()` reverse relationship added
- [x] Media collections: `'kyc-documents'` (PDF + images, max 20), `'partner-logo'` (single file)

### Filament Resource
- [x] `PartnerResource` — modular `Partners/Schemas/`, `Tables/`, `Pages/`, `RelationManagers/`
- [x] `PartnerForm` — 5 sections: Company Info, Tax & Legal (collapsed), Banking (collapsed), Status & Account, KYC Documents
- [x] `PartnerInfolist` — read-only view with status badges
- [x] `PartnersTable` — status badge, product count, `TrashedFilter`, soft delete actions
- [x] `PartnerProductsRelationManager` — `AttachAction` with pivot fields (adult/child prices + is_active), product names display via `$recordTitleAttribute = 'name'`
- [x] Status workflow: `pending` → `approved` → `rejected` with `approved_at` timestamp
- [x] Access control: `super_admin`, `admin`, `manager` only

---

## Actual Schema (as migrated)

```sql
CREATE TABLE partners (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name        VARCHAR(255) NOT NULL,
    trade_name          VARCHAR(255) NULL,
    registration_number VARCHAR(100) NULL,
    tax_number          VARCHAR(100) NULL,
    email               VARCHAR(255) UNIQUE NULL,
    phone               VARCHAR(50)  NULL,
    address             TEXT         NULL,
    city                VARCHAR(100) NULL,
    country             VARCHAR(100) NULL,
    bank_name           VARCHAR(255) NULL,
    bank_account        VARCHAR(100) NULL,
    bank_iban           VARCHAR(100) NULL,
    bank_swift          VARCHAR(50)  NULL,
    payment_terms_days  INT UNSIGNED DEFAULT 30,
    status              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_at         TIMESTAMP    NULL,
    is_active           TINYINT(1)   DEFAULT 1,
    notes               TEXT         NULL,
    deleted_at          TIMESTAMP    NULL,
    created_at          TIMESTAMP    NULL,
    updated_at          TIMESTAMP    NULL
);

CREATE TABLE partner_products (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id           BIGINT UNSIGNED NOT NULL,
    product_id           BIGINT UNSIGNED NOT NULL,
    partner_adult_price  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    partner_child_price  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active            TINYINT(1) DEFAULT 1,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY (partner_id, product_id)
);
```

---

## Key Architecture Decisions

1. **`AttachAction` not `CreateAction`** — The `PartnerProductsRelationManager` uses `AttachAction` so Filament writes a pivot row to `partner_products`. Using `CreateAction` on a `belongsToMany` would incorrectly try to create a new `Product` record.

2. **`$recordTitleAttribute = 'name'`** — Added to the RelationManager so the `AttachAction` dropdown reads `products.name` as the label. Without this, Filament falls back to the default model string which showed "product".

3. **Pivot model extends `Pivot`** — `PartnerProduct` extends `Illuminate\Database\Eloquent\Relations\Pivot` (not `Model`), with `$incrementing = true` because the `partner_products` table has an `id` column.

4. **Pricing display** — The product pricing table shows **both** the base price (gray text from `products`) and partner override price side by side so staff can compare at a glance.

5. **Soft deletes on `Partner`** — Follows global architectural rule: every entity uses `SoftDeletes`.
