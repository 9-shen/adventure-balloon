# Phase 17 — Driver Portal
**Status: 🔲 PENDING**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phase 6 (Transport), Phase 9 (Dispatch)  
**Est. Days:** 2–3

---

## Goal
A mobile-first, dedicated Filament panel at `/driver` for the `driver` role. This replaces the need for text-heavy WhatsApp messages by giving drivers a simple, interactive interface to view their daily assignments.

---

## Proposed Features

### 1. Dedicated Panel (`DriverPanelProvider`)
- Path: `/driver`
- Authentication: Scoped to the `driver` role.
- User linking: `driver_id` FK on the `users` table to link the auth user to their driver record.
- **Mobile First:** UI specifically tailored for phone screens (collapsed sidebars, card-based tables instead of wide data tables).

### 2. Driver Dashboard
- **Today's Mission:** Large, prominent widget showing today's assigned dispatch (Pickup times, locations).
- **Status Toggle:** Quick actions to mark themselves as "En Route", "Waiting", or "Completed".

### 3. Dispatch Manifest
- **My Dispatches:** List of past and future dispatches.
- **Manifest View:** Passenger list with WhatsApp click-to-chat links for easy communication.
- **Navigation Links:** Clickable map links to hotels/pickup zones.
