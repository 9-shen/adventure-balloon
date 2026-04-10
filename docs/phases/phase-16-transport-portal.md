# Phase 16 — Transport Portal
**Status: 🔲 NEXT**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phase 6 (Transport), Phase 9 (Dispatch)  
**Est. Days:** 2–3

---

## Goal
Extract the transport-company-specific views from the admin panel into a dedicated, isolated Filament panel at `/transport`. This allows transport partners to manage their fleet, drivers, and view dispatches without accessing the core `/admin` panel.

---

## Proposed Features

### 1. Dedicated Panel (`TransportPanelProvider`)
- Path: `/transport`
- Authentication: Scoped to the `transport` role.
- User linking: `transport_company_id` FK on the `users` table to identify which fleet they manage.

### 2. Transport Dashboard
- **Stats Widgets:** Active drivers, active vehicles, today's dispatches.
- **Recent Dispatches Chart:** Overview of upcoming assigned routes.

### 3. Fleet Management
- **My Vehicles:** Read-only or limited edit access to their vehicles.
- **My Drivers:** Read-only or limited edit access to their drivers.

### 4. Dispatch Viewer
- **My Dispatches:** View all dispatches assigned to their company.
- **Passenger Manifests:** Secure, read-only view of passengers for assigned routes.
