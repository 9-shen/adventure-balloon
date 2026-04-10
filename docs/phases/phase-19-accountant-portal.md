# Phase 19 — Accountant Portal
**Status: 🔲 PENDING**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phase 11 (Accountant), Phase 12 (Invoicing), Phase 13 (Reports)  
**Est. Days:** 2–3

---

## Goal
Extract the financial operations (billing, invoicing, payments, resolving balances) from the global `/admin` panel into a dedicated, highly focused `/finance` (or `/accountant`) Filament panel. This isolates sensitive financial data and provides a specialized dashboard for the accounting team without the clutter of operational settings or user management.

---

## Proposed Features

### 1. Dedicated Panel (`AccountantPanelProvider`)
- Path: `/finance`
- Authentication: Scoped to the `accountant` role.

### 2. Financial Dashboard
- **Cash Flow Widgets:** Daily/weekly revenue, total outstanding balances, unpaid invoices count.
- **Recent Activities:** Latest payments processed, recent invoices generated.

### 3. Financial Workflows (Migration)
- Move `AccountantBookingResource` (Process Payments).
- Move `InvoiceResource` and `PartnerInvoiceResource` (Invoicing).
- Move `TransportBillResource` and `TransporterBillingResource` (Transport Finance).
- Move Financial Reports (`RevenueReport`, `DuePaymentsReport`, `PartnerSummaryReport`).
