# Phase 13 — Financial Reports & Dashboard
**Status: 🔲 Pending**  
**Priority:** 🟡 MEDIUM  
**Depends On:** All booking/financial phases  
**Est. Days:** 4–5

---

## Goal
Comprehensive reporting for management — revenue breakdown, transport costs, due payments, PAX stats, and CSV exports.

---

## Checklist

### Reports to Build
- [ ] **Revenue Report** — Regular vs Partner bookings, by date range, product, payment status
- [ ] **Transport Cost Report** — payments made to transport companies
- [ ] **Due Payments Report** — all bookings with `balance_due > 0`
- [ ] **Client Statistics** — repeat customers, nationality breakdown, top sources
- [ ] **PAX & Flight Statistics** — total flights, total PAX, no-show rate by period
- [ ] **Partner Booking Summary** — per-partner: bookings count, revenue, outstanding

### Exports
- [ ] CSV/Excel export for ALL reports via Maatwebsite Excel (`Excel::download()`)

### Widgets (Admin Dashboard)
- [ ] Monthly revenue chart (line/bar)
- [ ] PAX volume chart
- [ ] Payment status distribution (pie/donut)
- [ ] Top products by revenue
