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

Each custom page uses a minimalistic blade view to ensure both widgets and table render natively:
```blade
<x-filament-panels::page>
    {{ $this->table }}
</x-filament-panels::page>
```

#### RevenueReport (sort 1)
- Uses `RevenueStatsWidget` natively injected via `getHeaderWidgets()`
- Filters: Date Range, Type, Product, Payment Status, Booking Status
- Export: Export All (Header Action) & Export Selected (Bulk Action)

#### DuePaymentsReport (sort 2)  
- Uses `DuePaymentsStatsWidget` natively injected via `getHeaderWidgets()`
- Only shows `balance_due > 0`, sorted descending

#### PartnerSummaryReport (sort 3)
- Per-partner: bookings count, total revenue, paid, outstanding, invoices count
- `withCount`/`withSum` Eloquent approach
- Date range filter via `whereHas`

#### PaxStatsReport (sort 4)
- Uses `PaxStatsWidget` natively injected via `getHeaderWidgets()`
- Grouped by `flight_date` + `type` (aggregate query)
- Custom `getTableRecordKey()` using composite key

#### TransportCostReport (sort 5)
- Tracks dispatch costs based on assigned vehicles
- Uses `TransportCostStatsWidget` for overall cost, billed, and unbilled aggregates
- Billed status indicator & CSV exports incorporating transport bill filters

### Dashboard & Page Widgets (`app/Filament/Admin/Pages/Reports/Widgets/` & `app/Filament/Admin/Widgets/`)
- Page Widgets (`StatsOverviewWidget`): `RevenueStatsWidget`, `DuePaymentsStatsWidget`, `PaxStatsWidget`, `TransportCostStatsWidget`
- Dashboard Widgets: `RevenueChartWidget` (line), `PaymentStatusChartWidget` (doughnut), `TopProductsWidget` (list).

---

## Filament v4 Gotchas

| Pattern | Correct | Wrong |
|---------|---------|-------|
| Table Custom Pages | Return minimal view `{{ $this->table }}` | Omit view, or manually build HTML layout |
| Stat Cards on Pages | Return via `getHeaderWidgets()` | Write custom blade UI blocks |
| Dynamic Widget Stats | Use `InteractsWithPageTable` trait | Ignore table filters |
| `$heading` on ChartWidget | `protected ?string $heading` | `protected static ?string $heading` |
| `$color` on ChartWidget | `protected string $color` | `protected static string $color` |
| `$columnSpan` | `protected array\|string\|int $columnSpan` | `protected int $columnSpan` |
| Aggregate groupBy key | Override `getTableRecordKey()` | Let it default to null id |
| Filters with closure | `filter()->query()` callback | `$this->getTableFiltersForm()` inside `query()` |

---

## Not Implemented (Deferred)
- Client Statistics (nationality, repeat customers) — deferred to Phase 15
