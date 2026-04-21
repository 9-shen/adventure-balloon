# Phase 24 — Booking Calendar
**Status: ✅ Complete**
**Priority:** 🟡 MEDIUM
**Depends On:** Phase 7 (Regular Booking), Phase 8 (Partner Booking)
**Completed:** 2026-04-21

---

## Goal

Add a **Booking Calendar** page to the Admin, Manager, and Accountant panels.
The calendar gives a visual month-at-a-glance overview of all bookings — showing
booking counts, revenue, and PAX per day — alongside monthly stats and today's
product breakdown in a sidebar.

---

## Panels It Appears In

| Panel     | Navigation Group | Can Add Booking |
|-----------|-----------------|-----------------| 
| Admin     | Bookings         | ✅ Yes           |
| Manager   | Bookings         | ✅ Yes           |
| Accountant| Bookings         | ❌ No (read-only)|

Access is controlled by `canView()` checking for roles:
`super_admin`, `admin`, `manager`, `accountant`.

---

## What It Shows

### Calendar Grid (left, 2/3 width)
- Month name + year, navigable with ← / → buttons
- 7-column grid (Sun–Sat)
- Each day with bookings shows:
  - A **purple pill badge**: `X bookings`
  - Revenue below the badge: `MAD X,XXX`
- Today's date highlighted with a primary-color circle
- Clicking a date **selects** it; clicking again deselects

### Sidebar (right, 1/3 width)
- **This Month** card: Total Bookings / Total Revenue / Avg. Booking Value
- **Selected Day** card (shown when a date is clicked): booking list with ref, product, amount, PAX
- **Today's Bookings** card (shown by default, hidden when a date is selected): product breakdown with PAX count

---

## Files Created / Modified

### New
- `app/Filament/Admin/Pages/BookingCalendarPage.php` — Livewire page (shared across panels)
- `resources/views/filament/admin/pages/booking-calendar.blade.php` — Blade view
- `docs/phases/phase-24-booking-calendar.md` — This document
- `resources/css/filament/admin/theme.css` — Custom Filament theme (admin panel)
- `resources/css/filament/manager/theme.css` — Custom Filament theme (manager panel)
- `resources/css/filament/accountant/theme.css` — Custom Filament theme (accountant panel)

### Modified
- `app/Providers/Filament/AdminPanelProvider.php` — `->viteTheme()` registered
- `app/Providers/Filament/ManagerPanelProvider.php` — registers page + adds "Bookings" nav group + `->viteTheme()`
- `app/Providers/Filament/AccountantPanelProvider.php` — registers page + adds "Bookings" nav group + `->viteTheme()`
- `vite.config.js` — 3 panel theme CSS files added to `input` array

---

## Data Queries

All bookings (regular + partner) come from the single `bookings` table.
Cancelled bookings are **excluded** (`booking_status != 'cancelled'`).

```php
// Per-day aggregates for the calendar grid
Booking::whereMonth('flight_date', $month)
    ->whereYear('flight_date', $year)
    ->whereNotIn('booking_status', ['cancelled'])
    ->selectRaw('flight_date, COUNT(*) as total_bookings,
                 SUM(final_amount) as total_revenue,
                 SUM(adult_pax + child_pax) as total_pax')
    ->groupBy('flight_date')
    ->get();
```

---

## Technical Notes

### Filament v4 Customs Page CSS — Why Custom Themes Were Required

Filament panels use an **isolated CSS build** separate from `app.css`. Custom Blade views
placed under `resources/views/filament/` are invisible to Filament's default CSS compilation,
which means Tailwind classes in those views are purged/absent at runtime.

**The fix**: create a custom Filament theme per panel using:

```bash
php artisan make:filament-theme admin
php artisan make:filament-theme manager
php artisan make:filament-theme accountant
```

Each generated `theme.css` already includes the correct `@source` directives:

```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';

@source '../../../../app/Filament/Admin/**/*';
@source '../../../../resources/views/filament/admin/**/*';
```

Then add to `vite.config.js`:

```js
input: [
    'resources/css/app.css',
    'resources/js/app.js',
    'resources/css/filament/admin/theme.css',
    'resources/css/filament/manager/theme.css',
    'resources/css/filament/accountant/theme.css',
],
```

And register in each panel provider:

```php
->viteTheme('resources/css/filament/admin/theme.css')
```

Finally run `npm run build` (or include it in deployment).

### ⚠️ CRITICAL: Deployment Requirement
`npm run build` **must run** during every Coolify/Docker deployment. Add it to `Dockerfile`
or the Coolify build command BEFORE `php artisan optimize`. Without it, panel CSS will be
missing and all Tailwind classes in custom Blade views will be unstyled.

### Filament v4 Page Property Gotchas (Documented Here for Future Reference)
- `protected static string $view` → **FATAL ERROR** — `Page::$view` is non-static in Filament 4.
  Use `public function getView(): string { return '...'; }` instead (or omit and use convention).
- `protected static ?string $navigationIcon` → triggers type incompatibility.
  Use `public static function getNavigationIcon(): string|\BackedEnum|null` with `Heroicon::*` enum.
- `protected static ?string $title` — OK to use as a static property (no type conflict).
- Navigation group and sort can use `getNavigationGroup()` / `getNavigationSort()` methods.

---

## Checklist

- [x] `BookingCalendarPage` Livewire page class with `previousMonth()`, `nextMonth()`, `selectDate()` methods
- [x] `getCalendarDays()` — builds padded grid with null cells for leading empty days
- [x] `getMonthStats()` — total bookings, revenue, avg value for current month
- [x] `getTodayBreakdown()` — today's bookings grouped by product with PAX count
- [x] `getSelectedDayBookings()` — detailed list for the selected date
- [x] Header action "Add Booking" (admin + manager only)
- [x] Blade view with 2-column layout (calendar | sidebar)
- [x] Dark mode support
- [x] Registered in Admin (auto-discovered), Manager, and Accountant panels
- [x] "Bookings" nav group added to Manager and Accountant panel providers
- [x] Custom Filament themes created for Admin, Manager, Accountant panels
- [x] `vite.config.js` updated with 3 theme CSS inputs
- [x] `->viteTheme()` registered in all 3 panel providers
- [x] `npm run build` completed — all 3 theme bundles compiled (~500KB each)
