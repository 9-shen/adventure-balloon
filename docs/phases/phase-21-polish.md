# Phase 15 — Polish & Advanced Features
**Status: 🔲 Pending**  
**Priority:** 🟢 LOW  
**Depends On:** All previous phases  
**Est. Days:** 3–5

---

## Goal
Final polish, advanced UX features, optimization, and mobile-readiness for field staff.

---

## Checklist

### Activity & Audit
- [ ] Activity log viewer in Filament (Spatie Activitylog table display)
- [ ] Audit trail report — who did what and when (searchable by user, date, model)

### Search & Filtering
- [ ] Global Filament search across bookings (by ref, customer name, email, phone)
- [ ] Advanced filter sets (date range, status, source, product, partner, payment)

### Bulk Operations
- [ ] Bulk confirm bookings
- [ ] Bulk cancel bookings
- [ ] Bulk export to CSV
- [ ] Bulk assign dispatch

### Import
- [ ] CSV import for bulk bookings (validate format, preview before import)

### Mobile Optimization
- [ ] Responsive layouts for Greeter panel (tablet/phone friendly)
- [ ] Driver panel (`/driver`) — mobile-first, show today's dispatch assignments
- [ ] Optimize tables for small screens (column hiding, card view)

### Dashboard Customization
- [ ] Widget visibility toggles by role
- [ ] Role-based dashboard stats (super_admin sees all, manager sees department-level)

### Performance
- [ ] Add database indexes on frequent query columns (`flight_date`, `booking_status`, `type`, `partner_id`)
- [ ] Cache product availability queries
- [ ] Eager load relationships to avoid N+1 in Filament tables
