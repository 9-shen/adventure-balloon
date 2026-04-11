# Phase 18 — Greeter Portal (Attendance Dashboard)
**Status: 🔲 PENDING**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phase 10 (Greeter Module)  
**Est. Days:** 2

---

## Goal
Migrate the existing Greeter module from the `/admin` panel into a dedicated, isolated panel at `/greeter`. This prevents field staff (greeters) from accessing the main admin interface and ensures a distraction-free, mobile-optimized experience.

---

## Proposed Features

### 1. Dedicated Panel (`GreeterPanelProvider`)
- Path: `/greeter`
- Authentication: Scoped to the `greeter` role exclusively.
- **Mobile Optimization:** Since greeters operate in the field (often on tablets or phones), the panel must use responsive cards and touch-friendly buttons.

### 2. Attendance Dashboard
- **Live Flight Stats:** Total expected passengers, checked-in count, remaining count.
- **Quick Switch:** Easy toggling between today's flights and tomorrow's flights.

### 3. Profile Management
- **Dedicated Profile Page:** Allow greeters to update their personal information (name, phone) and login credentials safely and easily inside their portal.

### 4. Check-in Interface (Migration)
- Move `GreeterCustomersRelationManager` and related views from the Admin panel to the new Greeter panel.
- Implement fast, touch-friendly "Show" / "No-Show" toggle buttons.
- Real-time parent booking synchronization.
