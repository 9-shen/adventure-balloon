# 🔐 Adventure Balloon — Security Enhancement Plan

> **Status:** PLANNING — awaiting green light before any code changes  
> **Audit Date:** May 2026  
> **Scope:** Laravel 12 · Filament 4 panels · Role/Portal access control · Middleware

---

## Clarified Requirements

Before listing issues, the following are **confirmed correct by design** and must NOT be changed:

| Rule | Status |
|------|--------|
| `super_admin` accesses `/admin` with full access to **all** resources — Bookings, Dispatches, Greeter, Accountant Module, Invoicing, Transport Finance, Reports, Financial Reports, Transport Management, Partner Management, Product Management, User Management, Settings | ✅ **Intentional — keep as-is** |
| `admin` accesses `/admin` with the same full resource access as super_admin | ✅ **Intentional — keep as-is** |
| All financial data (Finance Bookings, Invoices, Transport Bills, Financial Reports) is visible **inside `/admin`** for super_admin and admin | ✅ **Intentional — keep** |
| `/accountant` portal is for the `accountant` role **only** — `super_admin` and `admin` already see all financial data in `/admin` | 🆕 **Must be enforced in FIX-1** |
| `/partner` portal is for the `partner` role **only** — self-service booking and invoice viewing | ✅ **Already correct** |
| `http://127.0.0.1:8000/` (root URL) shows a **single universal login page** — after login, users are auto-redirected to their correct portal based on their role | ✅ **Intentional — keep** |
| `admin` cannot edit, update, or delete a `super_admin` user | 🆕 **New requirement — FIX-2** |

---

## Overview

The security audit identified **8 actionable issues** across 3 severity levels.

| Severity | Count | Summary |
|----------|-------|---------|
| 🔴 HIGH   | 2     | Role boundary violations — wrong roles access `/admin`; admin can modify super_admin |
| 🟠 MEDIUM | 6     | Mass-assignment risk, debug flag, email config gaps, dispatcher query scope, notification gaps, finance notification standard |
| 🟡 LOW    | 2     | Password strength, code robustness |
| **Total** | **10**| |

> **Note:** The previous draft listed FIX-2 (remove accountant resources from AdminPanelProvider) and FIX-5 (duplicate resource registration) as issues. These have been **removed** — admin/super_admin having full financial oversight in `/admin` is correct and intentional. The only admin-level security rule is the super_admin protection in FIX-2 below.

---

## Issues & Fix Plans

---

### 🔴 FIX-1 — Low-Privilege Role Users Can Sneak Into `/admin` Panel

**File:** `app/Models/User.php` — Lines 115–123

> ⚠️ **This fix has NOTHING to do with limiting what super_admin or admin can see.**
> `super_admin` and `admin` continue to see and control **everything** in `/admin` — zero change to their access.

**The actual problem — the REVERSE direction:**
A user account that has only the `greeter` role (a front-desk employee), or only the `transport` role, or only the `driver` role can type `yourdomain.com/admin` in their browser and **get in** — because the current fallthrough code accidentally grants them access to the full `/admin` panel.

```
Greeter employee → types /admin in browser → ✅ GRANTED  ← This is WRONG
Transport user   → types /admin in browser → ✅ GRANTED  ← This is WRONG
Driver           → types /admin in browser → ✅ GRANTED  ← This is WRONG
```

**Why it happens — current code (User.php line 116):**
```php
// This is the LAST return in canAccessPanel() — a catch-all fallthrough
// BUG: greeter, transport, driver, partner are accidentally included here
return $this->hasAnyRole([
    'super_admin', 'admin',    // ✅ correct — these belong in /admin
    'accountant',              // ⚠️ redundant — already handled by the if-block above
    'greeter',                 // 🚨 a greeter employee should NEVER enter /admin
    'transport',               // 🚨 a transport company user should NEVER enter /admin
    'driver',                  // 🚨 a driver should NEVER enter /admin
    // partner is in the fallthrough via the 'accountant' if-block leak
]);
```


**What is NOT changing — super_admin and admin keep 100% of their access:**
- `super_admin` → `/admin` ✅ full nav: Bookings, Dispatches, Greeter, Accountant Module, Invoicing, Transport Finance, Reports, Financial Reports, Transport Mgmt, Partner Mgmt, Product Mgmt, User Mgmt, Settings
- `admin` → `/admin` ✅ exactly the same full nav as super_admin — no change
- `admin`/`super_admin` → `/manager` ✅ — kept for operational oversight

**What gets fixed — only the intruder roles:**

| Role | Before fix | After fix |
|------|-----------|-----------|
| `super_admin` | `/admin` full access | `/admin` full access — **no change** |
| `admin` | `/admin` full access | `/admin` full access — **no change** |
| `greeter` | `/greeter` + could sneak into `/admin` 🚨 | `/greeter` only ✅ |
| `transport` | `/transport` + could sneak into `/admin` 🚨 | `/transport` only ✅ |
| `driver` | `/driver` + could sneak into `/admin` 🚨 | `/driver` only ✅ |
| `partner` | `/partner` + could sneak into `/admin` 🚨 | `/partner` only ✅ |
| `accountant` | `/accountant` + could enter `/admin` | `/accountant` only ✅ |

**Also fixed in the same change (Problem B):**
`admin`/`super_admin` are currently allowed into `/accountant` panel — but they don't need it since all financial data is already visible inside `/admin`. This is blocked as a cleanup.

**Planned Fix — replace chained `if` + fallthrough with a `match` expression:**

```php
public function canAccessPanel(Panel $panel): bool
{
    if (!$this->is_active) return false;

    return match ($panel->getId()) {
        // /admin — super_admin and admin ONLY — full access to everything
        // greeter/transport/driver are NOT listed here — they use their own portals
        'admin'      => $this->hasAnyRole(['super_admin', 'admin']),

        // /accountant — accountant role ONLY
        // super_admin/admin already have all financial data inside /admin
        'accountant' => $this->hasRole('accountant'),

        // /manager — manager + admin/super_admin for oversight
        'manager'    => $this->hasAnyRole(['manager', 'admin', 'super_admin']),

        // Each portal role is locked to its own portal only:
        'partner'    => $this->hasRole('partner') && $this->partner_id !== null,
        'transport'  => $this->hasRole('transport') && $this->transport_company_id !== null,
        'driver'     => $this->hasRole('driver') && $this->driver_id !== null,
        'guide'      => $this->hasRole('guide') && $this->guide_id !== null,
        'greeter'    => $this->hasRole('greeter'),
        'dispatcher' => $this->hasRole('dispatcher'),

        default      => false,  // explicit deny-all — safe for future panels
    };
}
```

**Files to change:** `app/Models/User.php`
**Risk of change:** Zero impact on super_admin/admin. Only closes the security gap for greeter/transport/driver/partner.
**Testing:** Log in as a greeter user → attempt `/admin` URL → redirected away. Log in as super_admin → `/admin` full access unchanged.

---

### 🟠 FIX-1B — Universal Login Controller — Default Fallback Is `/admin`

**File:** `app/Http/Controllers/Auth/UniversalLoginController.php` — Line 82

The root URL `http://127.0.0.1:8000/` shows a **single smart login form** that authenticates any role. After login, `resolvePanelUrl()` redirects the user to their correct portal:

```
https://yourdomain.com/  → Login form (universal entry point)
    ↓ submits credentials
    ↓ UniversalLoginController::login()
    ↓ resolvePanelUrl() maps role → portal URL
    ↓ redirect to /admin, /partner, /manager, /greeter, /driver ...
```

**Current redirect map (correctly implemented):**
```php
return match(true) {
    $user->hasAnyRole(['super_admin', 'admin']) => '/admin',
    $user->hasRole('manager')                  => '/manager',
    $user->hasRole('accountant')               => '/accountant',
    $user->hasRole('dispatcher')               => '/dispatcher',
    $user->hasRole('greeter')                  => '/greeter',
    $user->hasRole('transport')                => '/transport',
    $user->hasRole('driver')                   => '/driver',
    $user->hasRole('partner')                  => '/partner',   // ✅ partner handled
    $user->hasRole('guide')                    => '/guide',
    default                                    => '/admin',     // ⚠️ see note below
};
```

**Security note on `default => '/admin'`:**
If a user has an unrecognized role (e.g., a role was created but not added to this `match`), they are silently redirected to `/admin`. Since `canAccessPanel()` blocks access there, they'll hit the Filament auth wall — but it could be confusing. This is a **low-risk cosmetic issue** (no data exposure) but worth noting for future-proofing.

**Planned improvement:** Change `default => '/admin'` to redirect to a neutral "no access" page or back to login with an error message.

**Files to change:** `app/Http/Controllers/Auth/UniversalLoginController.php`
**Risk of change:** Very low — only affects the fallback for unrecognized roles



---

### 🔴 FIX-2 — `admin` Can Edit and Delete `super_admin` Users

**File:** `app/Filament/Admin/Resources/Users/UserResource.php`  
**Risk:** The `canDelete()` method partially protects the last super_admin from being deleted, but:
- An `admin` can still **edit** any `super_admin` user (change their name, email, password, role)
- An `admin` can **delete** a super_admin as long as there is more than one super_admin
- An `admin` can **demote** a super_admin by changing their role (if roles are editable in the UserForm)

This means an `admin` can effectively take over or lock out a `super_admin` account.

**Current code (partial protection only):**
```php
public static function canDelete($record): bool
{
    if ($record->id === Auth::id()) return false;

    // Only blocks delete if it's the LAST super_admin — not if there are 2+
    if ($record->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
        return false;
    }

    return true;  // ⚠️ admin can delete super_admin if there are 2+ super_admins
}

// ⚠️ canEdit() is not defined — defaults to true for all users
```

**Planned Fix:**
Add `canEdit()` and harden `canDelete()` in `UserResource` to deny any operation by an `admin` on a `super_admin` record:

```php
/**
 * admin cannot edit a super_admin user.
 * super_admin can edit anyone.
 */
public static function canEdit($record): bool
{
    /** @var \App\Models\User $authUser */
    $authUser = Auth::user();

    // admin cannot modify a super_admin
    if ($authUser->hasRole('admin') && $record->hasRole('super_admin')) {
        return false;
    }

    return true;
}

/**
 * admin cannot delete a super_admin.
 * No one can delete themselves.
 * The last super_admin cannot be deleted by anyone.
 */
public static function canDelete($record): bool
{
    /** @var \App\Models\User $authUser */
    $authUser = Auth::user();

    // No one can delete themselves
    if ($record->id === Auth::id()) return false;

    // admin cannot delete a super_admin (regardless of count)
    if ($authUser->hasRole('admin') && $record->hasRole('super_admin')) {
        return false;
    }

    // The last super_admin cannot be deleted by anyone
    if ($record->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
        return false;
    }

    return true;
}
```

**Additionally:** If the UserForm allows changing the `roles` field, add a guard so an `admin` cannot assign or remove the `super_admin` role:

```php
// In UserForm::configure() — restrict role select options based on auth user
Select::make('roles')
    ->options(function (): array {
        $user = Auth::user();
        $allRoles = \Spatie\Permission\Models\Role::pluck('name', 'name')->toArray();
        // admin cannot assign/see super_admin role
        if ($user->hasRole('admin')) {
            unset($allRoles['super_admin']);
        }
        return $allRoles;
    })
```

**Files to change:**
- `app/Filament/Admin/Resources/Users/UserResource.php`
- `app/Filament/Admin/Resources/Users/Schemas/UserForm.php` (role select guard)

**Risk of change:** Low — adds deny rules only; no existing functionality broken for super_admin  
**Testing:**
- Log in as `admin` → open a `super_admin` user → Edit button should be hidden/disabled
- Log in as `admin` → open a `super_admin` user → Delete button should be hidden/disabled
- Log in as `super_admin` → open any user → Edit/Delete work normally

---

### 🟠 FIX-3 — `ApplyEmailSettings` Middleware Missing From 4 Panels

**Files:**
- `app/Providers/Filament/ManagerPanelProvider.php`
- `app/Providers/Filament/DriverPanelProvider.php`
- `app/Providers/Filament/DispatcherPanelProvider.php`
- `app/Providers/Filament/AccountantPanelProvider.php`

**Risk:** If any email action is triggered synchronously from these panels (e.g., Manager manually resends a dispatch notification, Accountant resends an invoice), the SMTP transporter will use `.env` defaults (currently `null`) instead of the DB-configured SMTP settings. The send silently fails or throws an uncaught exception.

**Current State:**

| Panel | Has `ApplyEmailSettings` |
|-------|--------------------------|
| `/admin` | ✅ |
| `/partner` | ✅ |
| `/transport` | ✅ |
| `/guide` | ✅ |
| `/manager` | ❌ Missing |
| `/driver` | ❌ Missing |
| `/dispatcher` | ❌ Missing |
| `/accountant` | ❌ Missing |
| `/greeter` | ⬜ Intentionally excluded (no email actions) |

**Planned Fix:** Add `\App\Http\Middleware\ApplyEmailSettings::class` to the `->middleware([...])` block in each of the 4 missing providers.

**Files to change:** 4 PanelProvider files  
**Risk of change:** Very low — read-only middleware, no side effects  
**Testing:** Trigger a test email (re-send dispatch or invoice) from Manager/Dispatcher/Accountant panels; verify it uses DB SMTP settings

---

### 🟠 FIX-4 — `APP_DEBUG=true` Must Be `false` in Production

**File:** `.env` (server-side) — confirmed `APP_DEBUG=true`, `APP_ENV=local`  
**Risk:** If deployed to production without changing this flag, any PHP exception exposes full stack traces, DB credentials, `.env` values, and config data directly in the browser. This is a critical information disclosure vulnerability.

**Planned Fix (two parts):**

1. **Server-side** (deployment checklist — not a code change):
```ini
# On Coolify/Contabo VPS .env
APP_ENV=production
APP_DEBUG=false
```

2. **Code-side guard** in `AppServiceProvider::boot()`:
```php
// Warn in logs if debug is on in production
if (app()->isProduction() && config('app.debug')) {
    \Log::critical('SECURITY: APP_DEBUG=true detected in production!');
}
```

**Files to change:** `app/Providers/AppServiceProvider.php`  
**Risk of change:** Zero — read-only logging  
**Testing:** Set `APP_ENV=production` + `APP_DEBUG=true` locally; verify critical log entry appears

---

### 🟠 FIX-5 — Dispatcher `DispatchResource` Query Scope — Verify Coverage

**File:** `app/Filament/Dispatcher/Resources/DispatchResource.php`  
**Risk:** The Dispatcher's `BookingResource` correctly scopes with `whereIn('partner_id', $managedPartnerIds)`. The `DispatchResource` scopes through `whereHas('booking', ...)` — but if a dispatch record ever has `booking_id = null` or is linked to a booking without a partner assignment, the filter may not exclude it correctly.

**Planned Fix:** Audit and harden the `getEloquentQuery()` to explicitly handle null booking_id:

```php
public static function getEloquentQuery(): Builder
{
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $managedPartnerIds = $user->managedPartners()->pluck('partners.id');

    return parent::getEloquentQuery()
        ->whereHas('booking', function ($q) use ($managedPartnerIds) {
            $q->whereIn('partner_id', $managedPartnerIds);
        });
    // Note: dispatches with booking_id = null are excluded by whereHas (correct behavior)
}
```

**Files to change:** `app/Filament/Dispatcher/Resources/DispatchResource.php`  
**Risk of change:** Low — tightens an existing scope  
**Testing:** Create a dispatch for an unmanaged partner; verify dispatcher cannot see it

---

### 🟠 FIX-6 — FK Fields Mass-Assignable on `User` Model

**File:** `app/Models/User.php` — Lines 40–43  
**Risk:** `partner_id`, `transport_company_id`, `driver_id`, `guide_id` are in `$fillable`. Any code path that passes unfiltered request data to `User::update()` or `User::fill()` could allow an authenticated admin to reassign a user to a different partner/driver company — effectively granting them access to a different portal context without audit.

**Planned Fix:** Move FK fields out of `$fillable`. Update them only via explicit Eloquent assignment in the service layer:

```php
// Instead of mass assignment:
$user->update(['partner_id' => $data['partner_id']]);

// Use explicit assignment:
$user->partner_id = $data['partner_id'];
$user->save();
```

**Files to change:** `app/Models/User.php`, user save logic in `app/Filament/Admin/Resources/Users/`  
**Risk of change:** Medium — requires tracing all User write paths  
**Testing:** Verify admin user edit still saves partner/driver assignments correctly after change

---

### 🟡 FIX-7 — No Minimum Password Length on Profile Pages

**Files:**
- `app/Filament/Accountant/Pages/Profile.php`
- `app/Filament/Manager/Pages/Profile.php`
- `app/Filament/Guide/Pages/Profile.php`
- `app/Filament/Driver/Pages/Profile.php`
- `app/Filament/Greeter/Pages/Profile.php`
- `app/Filament/Dispatcher/Pages/Profile.php`

**Risk:** No minimum password length is enforced. Users can set single-character passwords on operational accounts (drivers, dispatchers), creating account takeover risk.

**Planned Fix:** Add `->minLength(8)->rules(['min:8'])` to the `new_password` field on all 6 Profile pages:

```php
TextInput::make('new_password')
    ->password()
    ->minLength(8)          // Add this
    ->rules(['min:8'])      // Add this
    ->requiredWith('new_password_confirmation'),
```

**Files to change:** 6 Profile pages  
**Risk of change:** Very low  
**Testing:** Attempt to save a password under 8 characters on each profile page; verify validation error appears

---

### 🟡 FIX-8 — `canAccessPanel()` Robustness — Use `match` with `default => false`

**File:** `app/Models/User.php`  
**Risk:** The current chained `if` structure means a developer adding a new panel in future without adding the matching `if` block will cause that role to fall through into `/admin`. This is a future-proofing issue.

**Planned Fix:** Resolved as part of FIX-1. The `match` expression with `default => false` handles this automatically.

**Dependency:** Resolved together with FIX-1 (same file, same change).

---

### 🟠 FIX-9 — Incomplete Email Notification Coverage for Assigned Parties

**Files:**
- `app/Filament/Admin/Resources/Bookings/Pages/EditBooking.php`
- `app/Settings/NotificationSettings.php`
- `app/Filament/Admin/Pages/Settings/NotificationSettingsPage.php`

**Risk:** While the system correctly notifies the Partner, Transport Company, and Drivers during key booking lifecycle events (Confirmation, Dispatch, Cancellation), it **fails to notify the assigned Guide**. Additionally, there is no configuration to notify the central Admin/Company email when an operational user (e.g., Dispatcher or Manager) cancels a confirmed booking. This leads to operational blind spots where guides may show up for cancelled bookings or miss confirmed ones.

**Current State (Notification Gaps):**
- **Booking Confirmation:** Partner is notified. **Guide is NOT notified.**
- **Booking Cancellation:** Partner, Transport, and Drivers are notified. **Guide is NOT notified.** Admin is NOT notified.

**Planned Fix:**
1. **Update Settings:** Add `booking_confirmed_guide_email`, `booking_cancelled_guide_email`, and `booking_cancelled_admin_email` toggles to `NotificationSettings.php` and `NotificationSettingsPage.php`.
2. **Update Cancellation Logic:** In `EditBooking.php` (and any other cancellation points), add logic to email the assigned `Guide` (if present) and the `Admin` when a booking is cancelled.
3. **Update Confirmation Logic:** In `EditBooking.php`, add logic to email the assigned `Guide` when a booking is confirmed.
4. **Create Notification Classes:** Create or update notification classes to handle Guide and Admin cancellation/confirmation templates.

**Files to change:** `NotificationSettings.php`, `NotificationSettingsPage.php`, `EditBooking.php`, and `app/Notifications/`.  
**Risk of change:** Low — adds new notification logic without altering core booking state transitions.  
**Testing:** Cancel a booking with an assigned guide and verify both the guide and admin receive cancellation emails.

---

### 🟠 FIX-10 — Inconsistent Finance Notification Settings

**Files:**
- `app/Settings/NotificationSettings.php`
- `app/Filament/Admin/Pages/Settings/NotificationSettingsPage.php`
- `app/Services/InvoiceService.php`
- `app/Services/TransportBillService.php`

**Risk:** Financial communications (invoices sent to partners and bills sent to transport companies) lacked unified control. Invoices were hardcoded to auto-send on generation, while Transport Bills had no notification logic at all. This lack of standardization could cause partners to be spammed with draft invoices, and transport companies to miss their generated bills.

**Current State (Finance Notification Gaps):**
- **Partner Invoices:** Unconditionally emailed on generation/send. Cannot be toggled off.
- **Transport Bills:** Never emailed. Manual external communication required.

**Planned Fix:**
1. **Update Settings:** Add `invoice_issued_partner_email` and `transport_bill_transport_company_email` toggles.
2. **Update InvoiceService:** Wrap the `InvoiceIssuedNotification` dispatch in a settings check.
3. **Update TransportBillService:** Create `TransportBillIssuedNotification` and dispatch it on generation/send if the toggle is enabled.

**Files to change:** `NotificationSettings.php`, `NotificationSettingsPage.php`, `InvoiceService.php`, `TransportBillService.php`, and `TransportBillIssuedNotification.php`.  
**Risk of change:** Low.  
**Testing:** Generate a transport bill and verify the transport company receives the email.

---

## Implementation Order

```
Step 1: FIX-1 + FIX-8  →  Rewrite canAccessPanel() — match + remove greeter/transport/driver from admin
         Files: app/Models/User.php

Step 2: FIX-2           →  Admin cannot edit/delete super_admin users
         Files: UserResource.php + UserForm.php

Step 3: FIX-3           →  Add ApplyEmailSettings to 4 missing panels
         Files: Manager, Driver, Dispatcher, Accountant PanelProviders

Step 4: FIX-7           →  Add minLength(8) to all 6 Profile pages
         Files: 6 Profile pages

Step 5: FIX-5           →  Verify + harden Dispatcher DispatchResource query scope
         Files: DispatchResource.php (Dispatcher namespace)

Step 6: FIX-6           →  Restrict FK mass-assignment on User model
         Files: User.php + User form save logic

Step 7: FIX-4           →  Add production debug guard to AppServiceProvider
         Files: AppServiceProvider.php

Step 8: FIX-9           →  Add Guide and Admin missing email notifications
         Files: NotificationSettings.php, EditBooking.php, Notification classes

Step 9: FIX-10          →  Standardize Finance email notifications (Invoices & Bills)
         Files: NotificationSettings.php, NotificationSettingsPage.php, InvoiceService.php, TransportBillService.php
```

---

## Files That Will Be Modified

| File | Fix(es) | Change Type |
|------|---------|-------------|
| `app/Models/User.php` | FIX-1, FIX-6, FIX-8 | `canAccessPanel()` rewrite + `$fillable` restriction |
| `app/Filament/Admin/Resources/Users/UserResource.php` | FIX-2 | Add `canEdit()`, harden `canDelete()` |
| `app/Filament/Admin/Resources/Users/Schemas/UserForm.php` | FIX-2 | Guard role select from admin assigning super_admin |
| `app/Providers/Filament/ManagerPanelProvider.php` | FIX-3 | Add middleware |
| `app/Providers/Filament/DriverPanelProvider.php` | FIX-3 | Add middleware |
| `app/Providers/Filament/DispatcherPanelProvider.php` | FIX-3 | Add middleware |
| `app/Providers/Filament/AccountantPanelProvider.php` | FIX-3 | Add middleware |
| `app/Filament/Dispatcher/Resources/DispatchResource.php` | FIX-5 | Query scope hardening |
| `app/Filament/Accountant/Pages/Profile.php` | FIX-7 | Validation rule |
| `app/Filament/Manager/Pages/Profile.php` | FIX-7 | Validation rule |
| `app/Filament/Guide/Pages/Profile.php` | FIX-7 | Validation rule |
| `app/Filament/Driver/Pages/Profile.php` | FIX-7 | Validation rule |
| `app/Filament/Greeter/Pages/Profile.php` | FIX-7 | Validation rule |
| `app/Filament/Dispatcher/Pages/Profile.php` | FIX-7 | Validation rule |
| `app/Providers/AppServiceProvider.php` | FIX-4 | Add production debug guard log |
| `app/Settings/NotificationSettings.php` | FIX-9, 10 | Add toggles |
| `app/Filament/Admin/Pages/Settings/NotificationSettingsPage.php` | FIX-9, 10 | Add toggles UI |
| `app/Filament/Admin/Resources/Bookings/Pages/EditBooking.php` | FIX-9 | Add guide/admin trigger logic |
| `app/Services/InvoiceService.php` | FIX-10 | Wrap notification in toggle check |
| `app/Services/TransportBillService.php` | FIX-10 | Add bill notification logic |

**Total: 20 files** across 9 steps.

---

## What Will NOT Change

| Item | Reason |
|------|--------|
| Accountant resources registered in `/admin` | ✅ Intentional — super_admin/admin need full financial oversight |
| admin/super_admin access to all `/admin` nav items | ✅ Intentional — no change |
| Universal login at `http://127.0.0.1:8000/` | ✅ Intentional — keep as single entry point for all roles |
| Partner portal `/partner` self-service features | ✅ Intentional — no change to partner capabilities |
| Greeter panel without `ApplyEmailSettings` | ✅ Intentional — no email actions in greeter portal |
| No database migrations | No schema changes required |
| No business logic code | All changes are access guards only |
| No notification or service code | Out of scope |

---

## Testing Checklist (Post-Implementation)

### Panel Access & Universal Login
- [ ] `http://127.0.0.1:8000/` shows login form (not redirecting unauthenticated users away) ✓
- [ ] `super_admin` logs in at `/` → auto-redirected to `/admin` ✓ full nav intact
- [ ] `admin` logs in at `/` → auto-redirected to `/admin` ✓ full nav intact
- [ ] `partner` logs in at `/` → auto-redirected to `/partner` ✓
- [ ] `manager` logs in at `/` → auto-redirected to `/manager` ✓
- [ ] `accountant` logs in at `/` → auto-redirected to `/accountant` ✓
- [ ] `greeter` logs in at `/` → auto-redirected to `/greeter` ✓
- [ ] `transport` logs in at `/` → auto-redirected to `/transport` ✓
- [ ] `driver` logs in at `/` → auto-redirected to `/driver` ✓
- [ ] `guide` logs in at `/` → auto-redirected to `/guide` ✓
- [ ] `dispatcher` logs in at `/` → auto-redirected to `/dispatcher` ✓
- [ ] Inactive user → blocked at universal login with clear error message ✓
- [ ] `super_admin` → `/accountant` direct URL ✗ **denied** (all financial data is in `/admin`) ✓
- [ ] `admin` → `/accountant` direct URL ✗ **denied** ✓
- [ ] `greeter` → `/admin` direct URL ✗ **denied** ✓
- [ ] `transport` → `/admin` direct URL ✗ **denied** ✓
- [ ] `driver` → `/admin` direct URL ✗ **denied** ✓
- [ ] `partner` → `/admin` direct URL ✗ **denied** ✓

### Super Admin Protection (FIX-2)
- [ ] `admin` opens `super_admin` user → **Edit button hidden/disabled** ✓
- [ ] `admin` opens `super_admin` user → **Delete button hidden/disabled** ✓
- [ ] `admin` cannot assign `super_admin` role via UserForm ✓
- [ ] `super_admin` can edit any user including other super_admins ✓
- [ ] `super_admin` can delete another super_admin (if more than 1 exist) ✓

### Email & Middleware (FIX-3)
- [ ] Email sent from Manager panel uses DB SMTP settings ✓
- [ ] Email sent from Dispatcher panel uses DB SMTP settings ✓
- [ ] Email sent from Accountant panel uses DB SMTP settings ✓

### Password Policy (FIX-7)
- [ ] Password < 8 chars rejected on Accountant Profile ✓
- [ ] Password < 8 chars rejected on Manager Profile ✓
- [ ] Password < 8 chars rejected on Driver Profile ✓
- [ ] Password < 8 chars rejected on other portal Profiles ✓

### Dispatcher Scope (FIX-5)
- [ ] Dispatcher cannot see dispatches for unmanaged partners ✓

---

*Awaiting green light to begin implementation.*
