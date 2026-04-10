# Phase 14 — Notifications & Automation
**Status: ✅ COMPLETE**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phase 2 (Settings), all data phases  
**Completed:** 2026-04-10

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

## Implemented ✅

### 1. Partner Booking Alert — Email to Admin
**Trigger:** When a booking of type `partner` is created (admin panel or partner portal)  
**Recipients:** App email (`AppSettings::company_email`) — ⚠️ Note: property is `company_email`, NOT `email`  
**Content:** Partner company name, booking ref, flight date, PAX count, product, final amount  

- [x] `PartnerBookingNotification` class — `toMail()` to admin email via `AnonymousNotifiable`
- [x] Fired in `CreateBooking::afterCreate()` when `$booking->type === 'partner'`
- [x] Fired in `CreatePartnerBooking::afterCreate()` (partner portal) — same hook
- [x] Subject: `"New Partner Booking: PBX-REF — PartnerName"`
- [x] Wrapped in try/catch — failures log but never crash booking creation

---

### 2. Invoice Issued — Email to Partner
**Trigger:** When `InvoiceService::generate()` creates a new invoice  
**Recipients:** `Partner::email`  
**Content:** Invoice ref, period, line items count, total amount, PDF attachment  

- [x] `InvoiceIssuedNotification` class — `toMail()` with PDF inline attachment
- [x] Fired inside `InvoiceService::generate()` after invoice persisted
- [x] PDF attached via `attachData($pdfContent, 'invoice-REF.pdf')` — no temp file needed
- [x] Subject: `"Invoice INV-REF from CompanyName"`
- [x] Guard: skips silently if `partner->email` is null, logs warning

---

### 3. Invoice Sent Action — Re-send Email
**Trigger:** When admin clicks **"Mark Sent"** on an invoice  

- [x] Re-uses `InvoiceIssuedNotification` with `isResend: true` constructor flag
- [x] Subject when resend: `"Invoice INV-REF — Payment Requested"`
- [x] Fired inside `InvoiceService::markSent()`

---

## Infrastructure

### Notifications Queue
- [x] All 3 notification classes implement `ShouldQueue` + `Queueable`
- [x] `queue = 'notifications'` set on each class
- [x] Queue worker: `php artisan queue:work --queue=notifications,default`

### Error Handling Pattern
All notification calls follow this pattern (consistent with Phase 9):
```php
try {
    $partner->notify(new InvoiceIssuedNotification($invoice, $pdfContent));
} catch (\Exception $e) {
    Log::error("InvoiceService: failed to email partner [{$invoice->invoice_ref}]: " . $e->getMessage());
}
```

### ⚠️ Critical Bug Fixed
`AppSettings::$email` does NOT exist. The correct property is `company_email`.
This caused 500 errors in 3 files. All fixed:
- `CreatePartnerBooking::afterCreate()` — `->company_email` ✅
- `CreateBooking::afterCreate()` (admin) — `->company_email` ✅
- `InvoiceIssuedNotification::toMail()` — `->company_email` ✅

---

## Email Transport
- SMTP settings come from `EmailSettings` (Phase 2) — applied at runtime via `ApplyEmailSettings` middleware
- All notification classes should use `->subject()`, `->greeting()`, `->line()`, `->action()` Mailable fluent API
- In development: use Mailtrap or `MAIL_MAILER=log` for safe testing

---

## Testing Done ✅

- [x] Create a partner booking (partner portal) → booking created successfully, admin email alert fires
- [x] Generate invoice → partner receives PDF email attachment
- [x] Click "Mark Sent" → partner receives re-send with "Payment Requested" subject
- [x] Local SMTP (no mail configured) → booking still created; email fails silently with log entry only
- [x] WhatsApp flows (Phase 9) confirmed unchanged

---

## Deferred (Phase 16)
- [ ] `BookingConfirmedNotification` → customer email
- [ ] `BookingCanceledNotification` → customer email
- [ ] `PaymentReminderNotification` → partner email (scheduled recurring job)
- [ ] Notification log viewer in Filament (read-only table from `notifications` DB table)
