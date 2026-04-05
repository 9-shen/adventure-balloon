# Phase 3 — User Management
**Status: 🔲 Pending**  
**Priority:** 🔴 HIGH  
**Depends On:** Phase 1 ✅  
**Est. Days:** 2–3

---

## Goal
Full user CRUD via Filament, role assignment, KYC profile fields, avatar upload, and per-user permission viewer.

---

## Files to Create

```
database/migrations/
└── xxxx_add_kyc_fields_to_users_table.php

app/Filament/Admin/Resources/
└── UserResource.php
    └── Pages/
        ├── ListUsers.php
        ├── CreateUser.php
        └── EditUser.php
```

---

## Checklist

### Database
- [ ] Migration: add KYC columns to `users` table
  - `phone` — string, nullable
  - `national_id` — string, nullable
  - `nationality` — string, nullable
  - `date_of_birth` — date, nullable
  - `address` — text, nullable
  - `is_active` — boolean, default true
  - `last_login_at` — timestamp, nullable

### Filament Resource
- [ ] `UserResource` with full CRUD
- [ ] Role assignment — `Select` dropdown from Spatie roles
- [ ] Avatar upload via Spatie Media Library
- [ ] Toggle `is_active` status
- [ ] Show `last_login_at` (read-only)
- [ ] Permission viewer — list of permissions per user (read-only tab)

### Access Control
- [ ] Only `super_admin` and `admin` can access `UserResource`
- [ ] `super_admin` can assign any role
- [ ] `admin` can assign all roles except `super_admin`

---

## Key Schema

```sql
ALTER TABLE users
    ADD phone          VARCHAR(30)  NULL,
    ADD national_id    VARCHAR(50)  NULL,
    ADD nationality    VARCHAR(100) NULL,
    ADD date_of_birth  DATE         NULL,
    ADD address        TEXT         NULL,
    ADD is_active      BOOLEAN      NOT NULL DEFAULT 1,
    ADD last_login_at  TIMESTAMP    NULL;
```

---

## Notes
- Track `last_login_at` via a `Login` event listener
- Avatar stored as Spatie media collection `'avatar'` on the User model
