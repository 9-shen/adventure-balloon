# Phase 8 — Partner Booking System
**Status: 🔲 Pending**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phases 5, 7  
**Est. Days:** 3–4

---

## Goal
Partner users can create bookings via their own panel (`/partner`). Same 5-step wizard as Phase 7 but uses partner custom pricing and stores `type = 'partner'` in the unified bookings table.

---

## Checklist

### Partner Panel Setup
- [ ] Create `/partner` Filament panel (`PartnerPanelProvider`)
- [ ] Restrict access to users with `partner` role

### Booking Wizard (Partner)
- [ ] Reuse or extend Phase 7 wizard
- [ ] Auto-load partner prices from `partner_products` pivot for the selected product
- [ ] Set `type = 'partner'` and `partner_id = auth()->user()->partner_id` automatically
- [ ] Reference format: `PBX-YYYY-XXXX`

### Invoice Auto-Generation
- [ ] Partner bookings do NOT generate individual invoices on creation
- [ ] Invoices are generated monthly (batch) in Phase 12

---

## Notes
- The partner panel shows ONLY that partner's own bookings
- Partner cannot see pricing of other partners
- Admin can create partner bookings from the admin panel too (Phase 7 wizard with `type` selector)
