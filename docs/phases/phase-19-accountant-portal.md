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
- Path: `/accountant`
- Authentication: Scoped to the `accountant` role.
- Include a dedicated **Profile Page** for accountants to manage their credentials, similar to other portals.

### 2. Navigation Structure & Migration
Move resources from the Admin panel to the Accountant panel with the following grouping:

#### Accountant Module
- **Finance Bookings:** Migrate `AccountantBookingResource`

#### Invoicing
- **Partners & Bookings:** Migrate `PartnerInvoiceResource` (or create if billing context)
- **Invoices:** Migrate `InvoiceResource`

#### Transport Finance
- **Transporters & Dispatches:** Migrate `TransporterBillingResource`
- **Transport Bills:** Migrate `TransportBillResource`

#### Financial Reports
- **Revenue Report:** Migrate `RevenueReport` page
- **Due Payments:** Migrate `DuePaymentsReport` page
- **Partner Summary:** Migrate `PartnerSummaryReport` page
- **PAX Statistics:** Migrate `PaxStatisticsReport` page
- **Transport Cost Report:** Migrate `TransportCostReport` page

### 3. Financial Dashboard & Widgets
- Built-in widgets for the Dashboard giving an overview of the financials:
  - Daily/Weekly revenue
  - Total outstanding balances
  - Unpaid invoices count
  - Recent latest payments processed
  - Recent invoices generated
