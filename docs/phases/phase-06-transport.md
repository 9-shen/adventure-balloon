# Phase 6 — Transport Management
**Status: ✅ COMPLETE** — Completed 2026-04-06  
**Priority:** 🟠 MEDIUM-HIGH  
**Est. Days:** 4–5

---

## Goal
Manage transport companies, their vehicles, and drivers. The dispatch system (Phase 9) uses this data to assign transport to bookings.

---

## Checklist

### Database
- [x] `transport_companies` table
- [x] `vehicles` table
- [x] `drivers` table
- [x] `driver_vehicle` pivot table

### Models
- [x] `TransportCompany` model (with `HasMedia` for company logo, `SoftDeletes`)
- [x] `Vehicle` model (with `SoftDeletes`, `belongsTo(TransportCompany)`, `belongsToMany(Driver)`)
- [x] `Driver` model (with `HasMedia` for license docs, `SoftDeletes`, `isLicenseExpiringSoon()`)
- [x] All relationships wired up

### Filament Resources
- [x] `TransportCompanyResource` (with `VehiclesRelationManager` + `DriversRelationManager`)
- [x] `VehicleResource` (standalone, Transport Management nav group sort 2)
- [x] `DriverResource` (standalone, license document upload, sort 3)

### Driver-Vehicle Assignment (Phase 6.1)
- [x] `Vehicles/RelationManagers/DriversRelationManager` — `AttachAction` filtered by same `transport_company_id`, `is_default` pivot toggle, license expiry warning in table
- [x] `Drivers/RelationManagers/VehiclesRelationManager` — `AttachAction` filtered by same `transport_company_id`, `is_default` pivot toggle, vehicle type badges
- [x] `VehicleResource::getRelations()` — registered `DriversRelationManager`
- [x] `DriverResource::getRelations()` — registered `VehiclesRelationManager`
- [x] Both show "Default Driver / Default Vehicle" green checkmark from pivot `is_default` flag
- [x] "Set as Default" inline action on both relation manager tables via `EditAction`

---

## Actual Schema (as migrated)

### `transport_companies`
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| company_name | VARCHAR(255) | required |
| contact_name | VARCHAR(255) | nullable |
| email | VARCHAR(255) | nullable |
| phone | VARCHAR(50) | nullable |
| address | TEXT | nullable |
| bank_name | VARCHAR(255) | nullable |
| bank_account | VARCHAR(100) | nullable |
| bank_iban | VARCHAR(100) | nullable |
| is_active | BOOLEAN | default true |
| notes | TEXT | nullable |
| deleted_at | TIMESTAMP | soft deletes |
| created_at/updated_at | TIMESTAMP | |

### `vehicles`
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| transport_company_id | FK → transport_companies | cascade delete |
| make | VARCHAR(100) | e.g. Mercedes |
| model | VARCHAR(100) | e.g. Sprinter |
| plate_number | VARCHAR(50) UNIQUE | |
| capacity | UNSIGNED INT | number of passengers |
| vehicle_type | ENUM(van, minibus, bus, car) | default van |
| price_per_trip | DECIMAL(10,2) | default 0 |
| is_active | BOOLEAN | default true |
| notes | TEXT | nullable |
| deleted_at | TIMESTAMP | soft deletes |

### `drivers`
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| transport_company_id | FK → transport_companies | cascade delete |
| name | VARCHAR(255) | |
| phone | VARCHAR(50) | WhatsApp number for dispatch notifications |
| national_id | VARCHAR(100) | CIN, nullable |
| license_number | VARCHAR(100) | nullable |
| license_expiry | DATE | nullable, triggers red warning ≤30 days |
| is_active | BOOLEAN | default true |
| notes | TEXT | nullable |
| deleted_at | TIMESTAMP | soft deletes |

### `driver_vehicle` (pivot)
| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT UNSIGNED PK | |
| driver_id | FK → drivers | cascade delete |
| vehicle_id | FK → vehicles | cascade delete |
| is_default | BOOLEAN | driver's primary vehicle |
| created_at/updated_at | TIMESTAMP | |
| UNIQUE | (driver_id, vehicle_id) | a driver can only link once per vehicle |

---

## Architecture Decisions

1. **Methods over static properties** — `getNavigationGroup()`, `getNavigationIcon()` used instead of `$navigationGroup`, `$navigationIcon` static properties. PHP 8.2 enforces strict property type inheritance from Filament's `Resource` class (`string|UnitEnum|null` vs `?string`) causing `Fatal error: Type of...` if overridden.

2. **Bulk action namespace** — All bulk actions (`BulkActionGroup`, `DeleteBulkAction`, `RestoreBulkAction`, `ForceDeleteBulkAction`) imported from `Filament\Actions` (NOT `Filament\Tables\Actions`) in Filament v4. Using wrong namespace causes 500 errors on list pages.

3. **Dual access pattern** — Vehicles and Drivers available both as standalone resources (sidebar links) and inline via `VehiclesRelationManager` / `DriversRelationManager` on the TransportCompany edit page. This lets admins manage everything from one place.

4. **License expiry warning** — `Driver::isLicenseExpiringSoon()` method returns `true` when license expires within 30 days. Table columns use `->color(fn ($record) => $record?->isLicenseExpiringSoon() ? 'danger' : null)` to show red cells automatically.

5. **No custom Pivot model needed** — The `driver_vehicle` pivot uses `->withPivot('is_default')` without a custom Pivot class, since no extra business logic is needed on that pivot (Phase 9 dispatch will handle assignment logic separately).
