# Phase 20 — Manager Portal
**Status: ✅ COMPLETE**  
**Priority:** 🟡 MEDIUM  
**Depends On:** All operational phases  
**Est. Days:** 2–3

---

## Goal
Create a focused, high-level operational overview panel at `/manager` tailored specifically for the `manager` role. This dashboard abstracts away raw configurations (settings, user permissions) and focuses entirely on day-to-day business operations, dispatch health, and performance metrics.

---

## Proposed Features

### 1. Dedicated Panel (`ManagerPanelProvider`)
- Path: `/manager`
- Authentication: Scoped to the `manager` role.

### 2. Management Dashboard
- **Operational Health:** Overbooked flights, flights with no dispatches, delayed payments.
- **Top Metrics:** aggregated revenue charts, top performing partners, most popular products.

### 3. Operational Oversight
- Full read-write access to Bookings, Dispatches, and Partners.
- Limited access to Products and Transport availability.
- No access to system settings or user KYC.
