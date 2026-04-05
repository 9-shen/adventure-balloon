# Phase 5 — Partner Management
**Status: 🔲 Pending**  
**Priority:** 🟠 MEDIUM-HIGH  
**Depends On:** Phase 4  
**Est. Days:** 3–4

---

## Goal
Manage partner companies with custom adult/child pricing per product, KYC documents, and approval workflow.

---

## Checklist

### Database
- [ ] `partners` table
- [ ] `partner_products` pivot table (custom pricing)

### Models
- [ ] `Partner` model with `HasMedia`
- [ ] Relationship: Partner `belongsToMany` Product through `partner_products`

### Filament Resource
- [ ] `PartnerResource` CRUD (all company fields)
- [ ] Custom pricing tab — select product, set adult/child price
- [ ] KYC document upload (media collection `'kyc-documents'`)
- [ ] Status workflow (pending → approved → rejected) with `SelectAction`
- [ ] Link user account to partner company

---

## Key Schema

```sql
CREATE TABLE partners (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name        VARCHAR(255) NOT NULL,
    contact_name        VARCHAR(255) NULL,
    email               VARCHAR(255) NULL,
    phone               VARCHAR(50)  NULL,
    address             TEXT         NULL,
    tax_number          VARCHAR(100) NULL,
    bank_name           VARCHAR(255) NULL,
    bank_account        VARCHAR(100) NULL,
    bank_iban           VARCHAR(100) NULL,
    status              ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    approved_at         TIMESTAMP    NULL,
    notes               TEXT         NULL,
    created_at          TIMESTAMP    NULL,
    updated_at          TIMESTAMP    NULL
);

CREATE TABLE partner_products (
    id                   BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id           BIGINT UNSIGNED NOT NULL,
    product_id           BIGINT UNSIGNED NOT NULL,
    partner_adult_price  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    partner_child_price  DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    created_at           TIMESTAMP NULL,
    updated_at           TIMESTAMP NULL,
    FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY (partner_id, product_id)
);
```
