# Phase 12 — Invoicing System
**Status: 🔲 Pending**  
**Priority:** 🟠 MEDIUM-HIGH  
**Depends On:** Phases 5, 8, 11  
**Est. Days:** 4–5

---

## Goal
Generate professional PDF invoices for partner bookings on a monthly batch basis. Track payment of invoices.

---

## Invoice Reference Format
`INV-2026-0001`

---

## Checklist

### Database
- [ ] `invoices` table
- [ ] `invoice_items` table (one per booking line)

### Features
- [ ] `InvoiceResource` CRUD
- [ ] Invoice generation from a set of partner bookings (by partner + date range)
- [ ] PDF generation (DomPDF) — professional layout with company branding
- [ ] Email invoice PDF to partner
- [ ] Mark invoice as paid (update `paid_at`, `payment_reference`)
- [ ] `InvoiceService::generate(Partner $partner, Carbon $from, Carbon $to): Invoice`
- [ ] `InvoiceService::send(Invoice $invoice): void`
- [ ] `InvoiceService::markPaid(Invoice $invoice, string $ref): void`

---

## PDF Layout
```
Header  : Company Logo | Invoice Number | Date | Due Date
Bill To : Partner company name & address
Line    : Date | Booking Ref | Description | Adults | Children | Unit Price | Total
Summary : Subtotal | Tax | TOTAL DUE
Footer  : Bank details | Payment terms
```

---

## Key Schema

```sql
CREATE TABLE invoices (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_ref        VARCHAR(20)  NOT NULL UNIQUE,
    partner_id         BIGINT UNSIGNED NOT NULL,
    period_from        DATE NOT NULL,
    period_to          DATE NOT NULL,
    subtotal           DECIMAL(10,2) NOT NULL DEFAULT 0,
    tax_amount         DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_amount       DECIMAL(10,2) NOT NULL DEFAULT 0,
    status             ENUM('draft','sent','paid','overdue') NOT NULL DEFAULT 'draft',
    sent_at            TIMESTAMP NULL,
    paid_at            TIMESTAMP NULL,
    payment_reference  VARCHAR(255) NULL,
    notes              TEXT NULL,
    created_by         BIGINT UNSIGNED NULL,
    created_at         TIMESTAMP NULL,
    updated_at         TIMESTAMP NULL,
    FOREIGN KEY (partner_id) REFERENCES partners(id)
);

CREATE TABLE invoice_items (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id    BIGINT UNSIGNED NOT NULL,
    booking_id    BIGINT UNSIGNED NOT NULL,
    description   VARCHAR(255) NOT NULL,
    flight_date   DATE NOT NULL,
    adult_pax     INT NOT NULL DEFAULT 0,
    child_pax     INT NOT NULL DEFAULT 0,
    unit_price    DECIMAL(10,2) NOT NULL,
    line_total    DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id)
);
```
