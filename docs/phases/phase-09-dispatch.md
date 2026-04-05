# Phase 9 — Dispatch System
**Status: 🔲 Pending**  
**Priority:** 🟠 MEDIUM-HIGH  
**Depends On:** Phases 6, 7, 8  
**Est. Days:** 5–7

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
- [ ] `dispatches` table
- [ ] `dispatch_drivers` pivot table

### Models
- [ ] `Dispatch` model
- [ ] Relationships to `Booking`, `TransportCompany`, `Driver`, `Vehicle`

### Filament Resource
- [ ] `DispatchResource` — create from a confirmed booking
- [ ] Transport company selector
- [ ] Vehicle selector (filtered by company)
- [ ] Driver auto-suggest based on PAX / vehicle capacity
- [ ] Manual override of driver assignments
- [ ] Status management: Pending → Confirmed → In Progress → Delivered

### Notifications
- [ ] `DispatchAssignedNotification` → transporter email (full manifest)
- [ ] `DriverAssignedNotification` → driver WhatsApp via Twilio
- [ ] `DispatchService::notifyTransporter(Dispatch $dispatch): void`
- [ ] `DispatchService::notifyDrivers(Dispatch $dispatch): void`

### Service Layer
- [ ] `DispatchService::assignDrivers(Dispatch $dispatch): array`

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
