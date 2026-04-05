# Phase 11 — Accountant Module
**Status: 🔲 Pending**  
**Priority:** 🔴 HIGH — Financial control  
**Depends On:** Phases 7, 8, 10  
**Est. Days:** 3–4

---

## Goal
Give accountants a scoped financial view within the admin panel — payment management, revenue summary, and cross-checking attendance data.

---

## Checklist
- [ ] Accountant role access scoped in admin panel
- [ ] Financial overview list (all bookings + payment status)
- [ ] Payment adjustment (edit `payment_status`, `amount_paid`, `balance_due`)
- [ ] Attendance verification (cross-check greeter `attendance` data)
- [ ] Revenue summary (daily / weekly / monthly)
- [ ] Due payments list (filter: `balance_due > 0`)
- [ ] Filament Widgets:
  - [ ] `TotalRevenueWidget`
  - [ ] `OutstandingBalanceWidget`
  - [ ] `PaymentsByMethodWidget`
  - [ ] `RecentPaymentsWidget`
