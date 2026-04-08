# Phase 9 — Dispatch System
**Status: 🔄 In Progress**  
**Priority:** 🟠 MEDIUM-HIGH  
**Depends On:** Phases 6, 7, 8  
**Est. Days:** 5–7  
**Started:** 2026-04-08  
**Last Updated:** 2026-04-08

---

## Goal
Assign transport and drivers to confirmed bookings. Auto-send email to transporter and WhatsApp to each assigned driver on dispatch creation. Expose a manual "Send WhatsApp" button on the View page.

---

## Dispatch Reference Format
`DSP-YYYY-NNNN`

---

## Driver Assignment Algorithm
```
drivers_needed = ceil(total_pax / vehicle_capacity)
For each driver assigned:
  pax_assigned = min(remaining_pax, vehicle_capacity)
  remaining_pax -= pax_assigned
```

---

## Checklist

### Database
- [x] `dispatches` table — `dispatch_ref`, `booking_id`, `transport_company_id`, `flight_date`, `total_pax`, `pickup_location`, `dropoff_location`, `pickup_time`, `status` ENUM, `notes`, `notified_at`, `created_by`, soft deletes
- [x] `dispatch_drivers` pivot table — `dispatch_id`, `driver_id`, `vehicle_id`, `pax_assigned`, `status`, `whatsapp_sent`, `whatsapp_sent_at`

### Models
- [x] `Dispatch` model — `$fillable`, casts, `SoftDeletes`, `HasFactory`
- [x] `DispatchDriver` pivot model — extends `Model`, owns PK, `$fillable` with `whatsapp_sent` + `whatsapp_sent_at`
- [x] Relationships: `booking()`, `transportCompany()`, `createdBy()`, `dispatchDriverRows()`, `drivers()` BelongsToMany

### Service Layer
- [x] `DispatchService::generateRef()` — DSP-YYYY-NNNN sequential per year, collision-safe
- [x] `DispatchService::suggestDriverAssignments(int $totalPax, int $transportCompanyId)` — vehicle-capacity algorithm
- [x] `DispatchService::createDispatch(array $data)` — DB transaction: Dispatch::create() + dispatch_driver rows
- [x] `DispatchService::notifyTransporter(Dispatch $dispatch)` — fires `DispatchAssignedNotification` email to transport company, marks `notified_at`
- [x] `DispatchService::notifyDrivers(Dispatch $dispatch)` — fires `DriverAssignedNotification` to each driver (email via mail channel)
- [x] `DispatchService::sendWhatsAppToDrivers(Dispatch $dispatch)` — Twilio WhatsApp API per driver, marks `whatsapp_sent` + `whatsapp_sent_at`, returns `['sent', 'failed', 'skipped', 'errors']`
- [ ] `DispatchService::assignDrivers(Dispatch $dispatch): array` — auto-assign and write to DB (not just suggest)

### Notifications
- [x] `DispatchAssignedNotification` — email to TransportCompany (via `Notifiable`); body includes: dispatch ref, booking ref, schedule (date/time/pickup/dropoff), full passenger list with contacts, driver-vehicle assignments with plate numbers; branded with `AppSettings::company_name`
- [x] `DriverAssignedNotification` — email to Driver; body includes: dispatch ref, booking ref, flight date, pickup time/location/dropoff, vehicle info, PAX assigned
- [x] `twilio/sdk ^8.11.3` installed for WhatsApp messaging

### Filament Resource
- [x] `DispatchResource` — modular structure (`Dispatches/Schemas/`, `Pages/`)
- [x] `DispatchForm::configure()` — CREATE form: reactive booking selector + live info card + transport company + status + driver repeater + notes
- [x] `DispatchForm::forEdit()` — EDIT form: read-only booking block (`staticBookingComponents`) + editable logistics sections
- [x] Booking selector — searchable dropdown (confirmed bookings without dispatch; locked/disabled on edit; backed by `Hidden::make('booking_id')`)
- [x] Reactive booking info card — shows booking ref, type badge, flight date, PAX breakdown, partner details
- [x] Transport company selector (active companies only)
- [x] **Status management dropdown** — `pending | confirmed | in_progress | delivered | cancelled` on both Create and Edit forms; defaults to `pending` on create
- [x] Driver repeater (`buildDriverRepeater()`) — driver + vehicle (filtered by company via `../../transport_company_id`) + PAX assigned; collapsible with item labels
- [x] Manual override of driver assignments via repeater (add/remove rows)
- [x] `DispatchRef` generated and formatted as `DSP-YYYY-NNNN`
- [x] `ViewDispatch` page — **"Send WhatsApp to Drivers"** green header action button; confirmation modal; smart notifications (sent count / skipped / errors)
- [x] `CreateDispatch::afterCreate()` — automatically fires `notifyTransporter()` after creation; UI banner shows "✉️ Email sent to [company]" or warning if no email on file
- [ ] Driver auto-suggest button on Create/Edit (fill repeater automatically from algorithm)

### Bookings Integration
- [x] Booking status field added to **Create wizard Step 5** (Review & Confirm); defaults to `pending`
- [x] Pricing Summary read-only section added to **Edit Booking** form; shows adult/child unit price, adult total, child total, discount, final amount from saved record columns

### Navigation
- [x] Sidebar group order enforced in `AdminPanelProvider` via `->navigationGroups([...])`: Bookings → Transport Management → Partner Management → Product Management → User Management → **Settings (collapsed)**

---

## Architecture Decisions

- **`DispatchForm` split** — `configure()` for Create (reactive booking selector + live info card) vs `forEdit()` for Edit (static read-only booking block using `staticBookingComponents()`). Prevents Filament OOM issues from mixing reactive `Get` closures with `$record`-bound closures.
- **Booking locked on edit** — `booking_id` Select is `->disabled()` on edit and backed by a `Hidden::make('booking_id')` to preserve the value in form state.
- **Status field** — bound directly to `dispatches.status` ENUM column (already in `$fillable`). No migration needed.
- **Driver/vehicle filtering** — uses `../../transport_company_id` relative path in `Get $get` inside the repeater to traverse up two levels to the section scope.
- **`DispatchDriver` pivot** — extends `Model` (not `Pivot`) with its own PK and `$fillable`; loaded via `dispatchDriverRows()` `hasMany` relationship.
- **WhatsApp via Twilio** — reads `WhatsAppSettings` (account_sid, auth_token, from_number, enabled) from DB via Spatie Settings; guards against missing/disabled creds; normalises phone to `+NNNN` format; sends to `whatsapp:+NNNN`.
- **Auto-email on create** — `CreateDispatch::afterCreate()` calls `DispatchService::notifyTransporter()` immediately (synchronous if `QUEUE_CONNECTION=sync`); errors are caught and logged without crashing the page.
- **NavigationGroups** — defined in `AdminPanelProvider`, group name must exactly match each Resource's `getNavigationGroup()` return value. `->collapsed()` on Settings group collapses it by default.

---

## Key Schema

```sql
CREATE TABLE dispatches (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispatch_ref          VARCHAR(20)  NOT NULL UNIQUE,
    booking_id            BIGINT UNSIGNED NOT NULL,
    transport_company_id  BIGINT UNSIGNED NOT NULL,
    flight_date           DATE         NULL,
    total_pax             INT          NULL,
    pickup_location       VARCHAR(255) NULL,
    dropoff_location      VARCHAR(255) NULL,
    pickup_time           TIME         NULL,
    status                ENUM('pending','confirmed','in_progress','delivered','cancelled') NOT NULL DEFAULT 'pending',
    notes                 TEXT         NULL,
    notified_at           TIMESTAMP    NULL,
    created_by            BIGINT UNSIGNED NULL,
    created_at            TIMESTAMP    NULL,
    updated_at            TIMESTAMP    NULL,
    deleted_at            TIMESTAMP    NULL,
    FOREIGN KEY (booking_id)           REFERENCES bookings(id),
    FOREIGN KEY (transport_company_id) REFERENCES transport_companies(id)
);

CREATE TABLE dispatch_drivers (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    dispatch_id    BIGINT UNSIGNED NOT NULL,
    driver_id      BIGINT UNSIGNED NOT NULL,
    vehicle_id     BIGINT UNSIGNED NOT NULL,
    pax_assigned   INT NOT NULL DEFAULT 0,
    status         VARCHAR(50) NULL,
    whatsapp_sent  BOOLEAN NOT NULL DEFAULT 0,
    whatsapp_sent_at TIMESTAMP NULL,
    FOREIGN KEY (dispatch_id) REFERENCES dispatches(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id)   REFERENCES drivers(id),
    FOREIGN KEY (vehicle_id)  REFERENCES vehicles(id)
);
```
