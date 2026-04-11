# Phase 16 — Transport Portal
**Status: 🔲 NEXT**  
**Priority:** 🟡 MEDIUM  
**Depends On:** Phase 6 (Transport), Phase 9 (Dispatch)  
**Est. Days:** 3–4

---

## Goal
Extract the transport-company-specific views from the admin panel into a dedicated, isolated Filament panel at `/transport`. This gives transport partners a **self-service portal** to manage their own fleet (vehicles + drivers), assign drivers to vehicles, and view their dispatches — without any access to the core `/admin` panel.

---

## Database Prerequisites

### `transport_company_id` on `users` table
A `transport_company_id` nullable FK must exist on `users` to link a user to a specific transport company (same pattern as `partner_id` for the partner portal).

```php
// Migration: add_transport_company_id_to_users_table
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('transport_company_id')
          ->nullable()
          ->after('partner_id')
          ->constrained('transport_companies')
          ->nullOnDelete();
});
```

### Models
```php
// User.php
public function transportCompany(): BelongsTo
{
    return $this->belongsTo(TransportCompany::class);
}

// TransportCompany.php
public function users(): HasMany
{
    return $this->hasMany(User::class, 'transport_company_id');
}
```

---

## Proposed Features

### 1. Dedicated Panel (`TransportPanelProvider`)
- **Path:** `/transport`
- **Branding:** Orange/amber theme (distinct from admin blue and partner teal)
- **Authentication:** Scoped to the `transport` role only via `canAccessPanel()`
- **Discovery Namespace:** `App\Filament\Transport\`
- **User Linking:** `Auth::user()->transport_company_id` identifies which fleet the logged-in user manages

---

### 2. Transport Dashboard
- **Stats Widgets:**
  - Total active vehicles
  - Total active drivers
  - Today's assigned dispatches
  - Unassigned drivers count
- **Recent Dispatches:** Upcoming routes assigned to this transporter

---

### 3. Vehicle Management (Full Self-Service CRUD)

Transport users can **add, edit, and manage their own vehicles** without contacting admin.

**Resource:** `TransportVehicleResource`

#### Fields (Create / Edit)
| Field | Type | Notes |
|-------|------|-------|
| Make | Text | e.g., Toyota |
| Model | Text | e.g., HiAce |
| License Plate | Text | Unique |
| Type | Select | Minivan / Bus / SUV / Sedan |
| Capacity (seats) | Number | Used for auto-assign logic |
| Price per Trip | Decimal | MAD — used for billing |
| Is Active | Toggle | Defaults to true |

#### Scope
- Always scoped to `transport_company_id = Auth::user()->transport_company_id`
- Transporter cannot see vehicles from other companies
- Admin still has full view in the main panel

```php
// TransportVehicleResource — getEloquentQuery()
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('transport_company_id', Auth::user()->transport_company_id);
}
```

---

### 4. Driver Management (Full Self-Service CRUD)

Transport users can **add, edit, and manage their own drivers**.

**Resource:** `TransportDriverResource`

#### Fields (Create / Edit)
| Field | Type | Notes |
|-------|------|-------|
| Full Name | Text | |
| Phone | Text | |
| WhatsApp Number | Text | Used for dispatch notifications |
| License Number | Text | |
| License Expiry | Date | Optional, for tracking |
| Is Active | Toggle | Defaults to true |
| Photo | Media | Spatie Media Library |

#### Scope
- Always scoped to `transport_company_id = Auth::user()->transport_company_id`

```php
// TransportDriverResource — getEloquentQuery()
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('transport_company_id', Auth::user()->transport_company_id);
}
```

---

### 5. Driver–Vehicle Assignment
Transport users can assign their drivers to their vehicles (and set a default driver per vehicle).

**Options:**
- **Option A — Relation Manager on VehicleResource:** `DriversRelationManager` on the Vehicle edit/view page using `AttachAction`. This allows attaching existing drivers to a vehicle.
- **Option B — Dedicated Assignment Page:** A standalone `DriverAssignmentPage` that shows all vehicles as cards, each with a dropdown to select the assigned driver(s).

**Recommended: Option A (Relation Manager)**

```
VehicleResource
  └── Pages/
       ├── ListVehicles
       ├── CreateVehicle
       └── EditVehicle  ← has DriversRelationManager tab
```

#### Pivot Table Reference: `driver_vehicle`
```sql
driver_vehicle
  driver_id      FK → drivers.id
  vehicle_id     FK → vehicles.id
  is_default     BOOLEAN DEFAULT false   ← marks the primary driver
```

#### Relation Manager Behavior
- **AttachAction** — attach an existing driver from the same transport company
- **DetachAction** — remove a driver from a vehicle
- **Toggle Column** — `is_default` toggle to mark one as primary

#### Scope in `DriversRelationManager`
```php
// Filter the driver dropdown to only show drivers from the same company
->options(
    Driver::where('transport_company_id', Auth::user()->transport_company_id)
          ->pluck('full_name', 'id')
)
```

---

### 6. Dispatch Viewer (Read-Only)
- **My Dispatches:** All dispatches where `transport_company_id = Auth::user()->transport_company_id`
- **Columns:** Dispatch ref, booking ref, flight date, PAX count, assigned drivers, status badge
- **Row Action:** `View Manifest` → shows passenger list (read-only) for that dispatch
- **No creation or editing** — dispatches are created exclusively by admin

---

## Panel Access Control

```php
// User::canAccessPanel()
public function canAccessPanel(Panel $panel): bool
{
    if ($panel->getId() === 'transport') {
        return $this->hasRole('transport') && $this->transport_company_id !== null;
    }
    // ...
}
```

---

## Admin Integration

In the main admin panel, the `UserResource` form should include a **"Portal Access"** section for the `transport` role (same pattern as partner):

```
Portal Access section (visible when role = transport):
  - Transport Company (select) → sets transport_company_id FK on users
```

---

## File Structure

```
app/
├── Filament/
│   └── Transport/
│       ├── Pages/
│       │   └── Dashboard.php
│       ├── Resources/
│       │   ├── VehicleResource.php
│       │   │   ├── Pages/
│       │   │   │   ├── ListVehicles.php
│       │   │   │   ├── CreateVehicle.php
│       │   │   │   └── EditVehicle.php
│       │   │   └── RelationManagers/
│       │   │       └── DriversRelationManager.php
│       │   └── DriverResource.php
│       │       └── Pages/
│       │           ├── ListDrivers.php
│       │           ├── CreateDriver.php
│       │           └── EditDriver.php
│       └── Widgets/
│           └── TransportStatsWidget.php
└── Providers/
    └── Filament/
        └── TransportPanelProvider.php
```

---

## Implementation Checklist

- [ ] Migration: `add_transport_company_id_to_users_table`
- [ ] Update `User` model (`transportCompany()` BelongsTo)
- [ ] Update `TransportCompany` model (`users()` HasMany)
- [ ] Create `TransportPanelProvider` at `/transport`
- [ ] `Vehicle Management`: `VehicleResource` scoped to company
- [ ] `Driver Management`: `DriverResource` scoped to company
- [ ] `DriversRelationManager` on VehicleResource (AttachAction)
- [ ] `is_default` toggle on driver-vehicle pivot
- [ ] `Dispatch Viewer`: read-only dispatches + manifest popup
- [ ] `TransportStatsWidget` for dashboard
- [ ] `canAccessPanel()` updated for transport panel
- [ ] Admin `UserResource`: transport company selector for transport role
- [ ] Seed test transport user linked to a transport company

---

## Filament v4 Rules (Reminders)

- Use `getNavigationGroup()` method — not `$navigationGroup` property
- All actions from `Filament\Actions\*` not `Filament\Tables\Actions\*`
- `AttachAction` for pivot relations (not `CreateAction`)
- `$recordTitleAttribute` must be set on RelationManagers using `AttachAction`
- `protected string $view` (non-static) for any custom page views
