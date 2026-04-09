# Phase 12 — Invoicing System
**Status: ✅ COMPLETE** — Completed 2026-04-09
**Priority:** 🔴 HIGH — Partner financial workflow
**Depends On:** Phases 5, 8, 11
**Est. Days:** 4–5

---

## Goal
Partner-centric invoicing: browse partners + their bookings, multi-select bookings, generate grouped invoices with PDF output, track payment status.

---

## Invoice Reference Format
`INV-2026-0001`

---

## Completed ✅

### Database
- [x] `invoices` table (invoice_ref, partner_id, period_from/to, subtotal, tax_rate, tax_amount, total_amount, status ENUM draft|sent|paid|overdue, sent_at, paid_at, payment_reference, notes, created_by, soft_deletes)
- [x] `invoice_items` table (invoice_id, booking_id, description, flight_date, adult_pax, child_pax, unit_price, line_total)
- [x] `add_invoiced_at_to_bookings_table` — adds `invoice_id` FK + `invoiced_at` timestamp to bookings

### Models
- [x] `Invoice` model — with generateRef(), isDraft/Sent/Paid(), getStatusColor(), hasMany(InvoiceItem), belongsTo(Partner)
- [x] `InvoiceItem` model — belongsTo(Invoice), belongsTo(Booking)
- [x] `Partner` model — added invoices() HasMany, bookings() HasMany
- [x] `Booking` model — added invoice_id, invoiced_at fillable + isInvoiced() helper

### Service
- [x] `InvoiceService::generate(Partner, bookingIds[], meta)` — creates Invoice + InvoiceItems, stamps `invoiced_at` on each booking
- [x] `InvoiceService::generatePdf(Invoice)` — DomPDF render of invoice blade template
- [x] `InvoiceService::markPaid(Invoice, ref)` — updates status=paid, paid_at, payment_reference
- [x] `InvoiceService::markSent(Invoice)` — updates status=sent, sent_at

### PDF Template
- [x] `resources/views/pdf/invoice.blade.php` — professional A4 layout with header (company + invoice ref), Bill To section, line items table (date, ref, description, adults, children, unit price, amount), totals (subtotal, optional tax, total due), payment terms footer

### Filament Resources
- [x] `PartnerInvoiceResource` — Partners & Bookings list nav group: Invoicing, sort: 1
  - Table: company_name, total_bookings (badge), total_billed, total_paid, total_outstanding (color-coded), invoices_count, status
  - Filter: by partner status
  - Action: "View Bookings" → navigate to manage/bookings page
- [x] `ViewPartnerBookings` page (ManageRelatedRecords) — partner bookings drill-down
  - Booking table: ref, flight_date, product, PAX, total, paid, balance (color), payment badge, status badge, invoiced badge
  - **Advanced Filter: Date Range** (From Date + Until Date on flight_date) with indicators
  - Filter: payment_status, booking_status, not_invoiced toggle
  - Bulk Actions: "Add to Invoice" (skips already-invoiced), "Remove from Basket"
  - Header Action: "Create Invoice (N bookings)" → slide-over with tax_rate + notes → generates invoice → redirects to InvoiceResource view
- [x] `InvoiceResource` — Invoices list, sort: 2
  - Table: invoice_ref, partner.company_name, period, items_count, subtotal, total_amount, status badge, created_at, paid_at
  - Filters: status, partner (searchable), date range
  - Row Actions: View, Download PDF, Mark Sent, Mark Paid (with payment_reference slideOver)
- [x] `ViewInvoice` page — full detail (Invoice Details, Partner/Bill To, Financial Summary, Booking Lines repeatable table)
  - Header Actions: Download PDF, Mark Sent, Mark Paid
- [x] `ListPartnerInvoices` page
- [x] `ListInvoices` page

---

## Architecture Notes
- Resources live in `app/Filament/Admin/Resources/Invoicing/`
- `ViewPartnerBookings` extends `ManageRelatedRecords` (Filament's native page for parent→related table)
- `selectedForInvoice` is a Livewire public property tracking the basket of booking IDs between table interactions
- PDF uses `barryvdh/laravel-dompdf` (already installed) via `Barryvdh\DomPDF\Facade\Pdf`
- `discoverResources()` auto-picks up all new resources — no manual registration needed

---

## Filament v4 Gotchas Discovered
1. `protected static string $view` on a Resource Page causes fatal `Cannot redeclare non static $view` — use `getView(): string` method OR use a built-in page class like `ManageRelatedRecords` instead
2. `Filament\Tables\Actions\Action` does NOT exist in v4 — use `Filament\Actions\Action`
3. `Filament\Tables\Actions\BulkAction` does NOT exist in v4 — use `Filament\Actions\BulkAction`
4. `money()` on TextColumn returns `decimal` type which requires explicit `(float)` cast before `number_format()`
5. Table row `->url()` actions require `Filament\Actions\Action` (not a ViewAction)

---

## PDF Layout
```
Header  : Company Logo | Invoice Number | Date | Period | Status badge
Bill To : Partner company name, address, tax number, email
Period  : period_from → period_to (colored box)
Table   : Date | Booking Ref | Description | Adults | Children | Unit Price | Amount
Totals  : Subtotal | Tax (if > 0%) | TOTAL DUE (dark band)
Notes   : Optional notes field
Footer  : Payment Terms (partner.payment_terms_days days) | Company branding
```
