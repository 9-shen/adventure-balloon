# Phase 14 — Notifications & Automation
**Status: 🔲 Pending**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phase 2 (Settings), all data phases  
**Est. Days:** 3–4

---

## Goal
Queue-based email and WhatsApp notifications for all key events in the booking lifecycle.

---

## Checklist

### Notification Classes
- [ ] `BookingConfirmedNotification` → customer email (booking summary)
- [ ] `BookingCanceledNotification` → customer email (cancellation + reason)
- [ ] `DispatchAssignedNotification` → transporter email (full manifest)
- [ ] `DriverAssignedNotification` → driver WhatsApp via Twilio
- [ ] `InvoiceIssuedNotification` → partner email (with PDF attachment)
- [ ] `PaymentReminderNotification` → partner email

### Queue Jobs
- [ ] `SendBookingConfirmation` job
- [ ] `SendDispatchNotification` job
- [ ] `SendDriverWhatsApp` job
- [ ] `SendInvoiceEmail` job

### Infrastructure
- [ ] `WhatsAppService::send(string $to, string $message): void` (Twilio SDK)
- [ ] All notifications dispatched to `notifications` queue
- [ ] Notification log stored in `notifications` table (Laravel default)
- [ ] Retry failed notifications via Horizon or a custom Artisan command
- [ ] Notification log viewer in Filament (read-only table)

---

## Notes
- WhatsApp messages use Twilio's WhatsApp Sandbox in dev
- SMTP settings come from `EmailSettings` (Phase 2) — applied at runtime via middleware
- Queue worker: `php artisan queue:work --queue=notifications,default`
