# Phase 14 — Notifications & Automation
**Status: 🔄 IN PROGRESS**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phase 2 (Settings), all data phases  
**Est. Days:** 3–4

---

## Goal
Automated email notifications for key lifecycle events across bookings, dispatches, and invoicing.
WhatsApp notifications are already handled by the Twilio integration (Phase 9) and must NOT be modified.

---

## Already Implemented ✅ (Do NOT modify)

### WhatsApp — Drivers (Phase 9)
- [x] `DriverAssignedNotification` — WhatsApp via Twilio SDK when driver is assigned
- [x] `DispatchService::sendWhatsAppToDrivers()` — per-driver message with full itinerary
- [x] `DispatchService::notifyDrivers()` — fires driver notification emails
- [x] **"Send WhatsApp to Drivers"** header action on `ViewDispatch` page

### Dispatch Transporter Email (Phase 9)
- [x] `DispatchAssignedNotification` — rich HTML email to transport company
- [x] `DispatchService::notifyTransporter()` — fires on dispatch creation automatically
- [x] Auto-fires in `CreateDispatch::afterCreate()` with success/failure UI banner
- [x] **"Send Notifications"** header action on `EditDispatch` page for manual re-send

---

## To Implement 🔲

### 1. Partner Booking Alert — Email to Admin
**Trigger:** When a booking of type `partner` is created via admin panel  
**Recipients:** App email (`AppSettings::email`)  
**Content:** Partner company name, booking ref, flight date, PAX count, product, final amount  

**Implementation:**
- [ ] `PartnerBookingNotification` class — `toMail()` to admin email
- [ ] Fire in `CreateBooking::afterCreate()` when `$booking->type === 'partner'`
- [ ] Use `AppSettings::email` as the notification target
- [ ] Subject: `"New Partner Booking: {PBX-REF} — {PartnerName}"`

---

### 2. Invoice Issued — Email to Partner
**Trigger:** When `InvoiceService::generate()` creates a new invoice  
**Recipients:** `Partner::email`  
**Content:** Invoice ref, period, line items count, total amount, PDF attachment  

**Implementation:**
- [ ] `InvoiceIssuedNotification` class — `toMail()` with PDF inline attachment
- [ ] Fire inside `InvoiceService::generate()` after invoice is persisted
- [ ] Attach PDF using `generatePdf($invoice)` → `Attachment::fromData(fn() => $pdfContent, 'invoice.pdf')`
- [ ] Subject: `"Invoice {INV-REF} from {CompanyName}"`
- [ ] Guard: skip silently if `partner->email` is null; log warning

---

### 3. Invoice Sent Action — Optional Email Re-send
**Trigger:** When admin clicks **"Mark Sent"** on an invoice  
**Recipients:** `Partner::email`  
**Content:** Same as #2 (invoice details + PDF)  

**Implementation:**
- [ ] Re-use `InvoiceIssuedNotification` inside `InvoiceService::markSent()`
- [ ] Subject prefix updated to: `"Invoice {INV-REF} — Payment Requested"`

---

## Infrastructure

### Notifications Queue
- [ ] Dispatch all 3 notification classes to the `notifications` queue
- [ ] Queue worker already supported: `php artisan queue:work --queue=notifications,default`

### Error Handling Pattern
All notification calls must follow this established pattern (consistent with Phase 9):
```php
try {
    $partner->notify(new InvoiceIssuedNotification($invoice, $pdfContent));
} catch (\Exception $e) {
    Log::error("InvoiceService: failed to email partner [{$invoice->invoice_ref}]: " . $e->getMessage());
}
```

---

## Email Transport
- SMTP settings come from `EmailSettings` (Phase 2) — applied at runtime via `ApplyEmailSettings` middleware
- All notification classes should use `->subject()`, `->greeting()`, `->line()`, `->action()` Mailable fluent API
- In development: use Mailtrap or `MAIL_MAILER=log` for safe testing

---

## Testing Checklist

- [ ] Create a partner booking → check admin mailbox for alert email
- [ ] Generate invoice for a partner with email → check partner mailbox, verify PDF attached
- [ ] Click "Mark Sent" on invoice → check partner mailbox for re-send
- [ ] Generate invoice for partner WITHOUT email → confirm no crash, log warning only
- [ ] Verify WhatsApp flows still work unchanged after any code changes

---

## Deferred (Phase 15)
- `BookingConfirmedNotification` → customer email (requires customer email capture improvement)
- `BookingCanceledNotification` → customer email
- `PaymentReminderNotification` → partner email (scheduled recurring job)
- Notification log viewer in Filament (read-only table from `notifications` DB table)
