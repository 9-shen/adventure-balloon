# Phase 13 — Financial Reports & Dashboard
**Status: ✅ COMPLETE**  
**Completed:** 2026-04-09  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phases 7, 8, 11, 12  

---

## Goal
Comprehensive reporting for management — revenue breakdown, transport costs, due payments, PAX stats, and CSV exports. Plus rich dashboard chart widgets.

---

## Implemented

### Excel Exports (`app/Exports/`)
- `RevenueReportExport` — FromQuery, all booking revenue columns, filter-aware
- `DuePaymentsExport` — FromQuery, balance_due > 0, highest first
- `PartnerSummaryExport` — FromCollection, per-partner aggregates
- `PaxStatsExport` — FromCollection, grouped by flight_date

### Report Pages (`app/Filament/Admin/Pages/Reports/`)
All pages: `Page implements HasTable + InteractsWithTable`  
Access: `super_admin`, `admin`, `accountant`, `manager`  
Navigation group: **Financial Reports**

#### RevenueReport (sort 1)
- 4-stat bar: Total Revenue | Collected | Outstanding | Total Bookings
- Filters: Date Range, Type, Product, Payment Status, Booking Status
- Export: Revenue CSV

#### DuePaymentsReport (sort 2)  
- 3-stat bar: Total Outstanding | Due Bookings Count | Highest Single Balance
- Only shows `balance_due > 0`, sorted descending

#### PartnerSummaryReport (sort 3)
- Per-partner: bookings count, total revenue, paid, outstanding, invoices count
- `withCount`/`withSum` Eloquent approach
- Date range filter via `whereHas`

#### PaxStatsReport (sort 4)
- 4-stat bar: Total Flights | Total PAX | Avg PAX/Flight | No-Show Rate %
- Grouped by `flight_date` + `type` (aggregate query)
- Custom `getTableRecordKey()` using composite key

### Dashboard Widgets (`app/Filament/Admin/Widgets/`)
- `RevenueChartWidget` — Line chart, monthly revenue current year, brand red
- `PaymentStatusChartWidget` — Doughnut, Paid/Partial/Due/On-site distribution  
- `TopProductsWidget` — Stats overview, top 3 products by revenue this month

---

## Filament v4 Gotchas

| Pattern | Correct | Wrong |
|---------|---------|-------|
| `$heading` on ChartWidget | `protected ?string $heading` | `protected static ?string $heading` |
| `$color` on ChartWidget | `protected string $color` | `protected static string $color` |
| `$columnSpan` | `protected array\|string\|int $columnSpan` | `protected int $columnSpan` |
| Aggregate groupBy key | Override `getTableRecordKey()` | Let it default to null id |
| Filters with closure | `filter()->query()` callback | `$this->getTableFiltersForm()` inside `query()` |

---

## Not Implemented (Deferred)
- Transport Cost Report — requires dedicated transporter-payment model (Phase 15)
- Client Statistics (nationality, repeat customers) — deferred to Phase 15
