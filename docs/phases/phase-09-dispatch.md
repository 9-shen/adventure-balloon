# Phase 9 — Dispatch System
**Status: 🔄 In Progress**  
**Priority:** 🟠 MEDIUM-HIGH  
**Depends On:** Phases 6, 7, 8  
**Est. Days:** 5–7  
**Started:** 2026-04-08

---

## Goal
Assign transport and drivers to confirmed bookings. Notify transporter by email and each driver by WhatsApp.

---

## Dispatch Reference Format
`DSP-2026-0001`

---

## Driver Assignment Algorithm
```
drivers_needed = ceil(total_pax / vehicle_capacity)
For each driver assigned:
  pax_assigned = min(remaining_pax, vehicle_capacity)
  remaining_pax -= pax_assigned
```

## Checklist

### Database
- [x] `dispatches` table — `dispatch_ref`, `booking_id`, `transport_company_id`, `pickup_location`, `dropoff_location`, `pickup_time`, `status` ENUM, `notes`, `notified_at`, `created_by`, soft deletes
- [x] `dispatch_drivers` pivot table — `dispatch_id`, `driver_id`, `vehicle_id`, `pax_assigned`, `status`, `whatsapp_sent`, `whatsapp_sent_at`

### Models
- [x] `Dispatch` model — `$fillable`, casts, `SoftDeletes`, `HasFactory`
- [x] `DispatchDriver` pivot model
- [x] Relationships: `booking()`, `transportCompany()`, `createdBy()`, `dispatchDriverRows()`, `drivers()` BelongsToMany

### Filament Resource
- [x] `DispatchResource` — modular structure (`Dispatches/Schemas/`, `Pages/`)
- [x] `DispatchForm::configure()` — CREATE form with reactive booking selector + info card
- [x] `DispatchForm::forEdit()` — EDIT form with read-only booking block + editable logistics
- [x] Booking selector — searchable dropdown (confirmed bookings without dispatch; locked on edit)
- [x] Reactive booking info card — shows booking ref, type badge, flight date, PAX count, partner details
- [x] Transport company selector (active companies only, filtered by `is_active`)
- [x] **Status management dropdown** — `pending | confirmed | in_progress | delivered | cancelled` on both Create and Edit forms; defaults to `pending` on create
- [x] Vehicle selector (filtered by transport company)
- [x] Driver repeater (`DispatchForm::buildDriverRepeater()`) — driver + vehicle + PAX assigned; driver/vehicle filtered by selected transport company
- [x] Manual override of driver assignments via repeater (add/remove rows)
- [x] `Send Notifications` header action button (Edit page)
- [x] `Update Status` header action button (Edit page)
- [ ] Driver auto-suggest algorithm (`ceil(pax / capacity)`)

### Notifications
- [ ] `DispatchAssignedNotification` → transporter email (full manifest)
- [ ] `DriverAssignedNotification` → driver WhatsApp via Twilio
- [ ] `DispatchService::notifyTransporter(Dispatch $dispatch): void`
- [ ] `DispatchService::notifyDrivers(Dispatch $dispatch): void`

### Service Layer
- [ ] `DispatchService::assignDrivers(Dispatch $dispatch): array`

---

## Architecture Decisions

- **`DispatchForm` split** — `configure()` for Create (reactive booking selector + live info card) vs `forEdit()` for Edit (static read-only booking block using `staticBookingComponents()`). Prevents Filament OOM issues from mixing reactive `Get` closures with `$record`-bound closures.
- **Booking locked on edit** — `booking_id` Select is `->disabled()` on edit and backed by a `Hidden::make('booking_id')` to preserve the value in form state.
- **Status field** — bound directly to `dispatches.status` ENUM column (already in `$fillable`). No migration needed — column existed from initial dispatch migration.
- **Driver/vehicle filtering** — uses `../../transport_company_id` relative path in `Get $get` inside the repeater to traverse up two levels to the section scope.
- **`DispatchDriver` pivot** — extends `Model` (not `Pivot`) with its own PK and `$fillable`; loaded via `dispatchDriverRows()` `hasMany` relationship.

---

## Key Schema

```sql
CREATE TABLE dispatches (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispatch_ref          VARCHAR(20)  NOT NULL UNIQUE,
    booking_id            BIGINT UNSIGNED NOT NULL,
    transport_company_id  BIGINT UNSIGNED NOT NULL,
    pickup_location       VARCHAR(255) NULL,
    dropoff_location      VARCHAR(255) NULL,
    pickup_time           TIME         NULL,
    status                ENUM('pending','confirmed','in_progress','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes                 TEXT         NULL,
    notified_at           TIMESTAMP    NULL,
    created_by            BIGINT UNSIGNED NULL,
    created_at            TIMESTAMP    NULL,
    updated_at            TIMESTAMP    NULL,
    FOREIGN KEY (booking_id)           REFERENCES bookings(id),
    FOREIGN KEY (transport_company_id) REFERENCES transport_companies(id)
);

CREATE TABLE dispatch_drivers (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispatch_id  BIGINT UNSIGNED NOT NULL,
    driver_id    BIGINT UNSIGNED NOT NULL,
    vehicle_id   BIGINT UNSIGNED NOT NULL,
    pax_assigned INT NOT NULL DEFAULT 0,
    FOREIGN KEY (dispatch_id) REFERENCES dispatches(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id)   REFERENCES drivers(id),
    FOREIGN KEY (vehicle_id)  REFERENCES vehicles(id)
);
```
