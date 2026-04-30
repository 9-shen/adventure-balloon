# Phase 28: Dispatcher Role & Portal

## Overview
Introduce a new `dispatcher` role with a dedicated portal (`/dispatcher`). This role is responsible for controlling dispatches and managing bookings specifically for a designated set of assigned partners. The super admin and admin will have the ability to create dispatcher accounts and assign them to one or multiple partners.

## 1. Database & Relationships
To allow a dispatcher to manage multiple partners, we need a many-to-many relationship.
- **Migration:** Create a pivot table `dispatcher_partner` (columns: `user_id`, `partner_id`).
- **User Model:** Add a `managedPartners()` `belongsToMany` relationship to the `User` model pointing to the `Partner` model via the `dispatcher_partner` table.
- **Partner Model:** Add a `dispatchers()` `belongsToMany` relationship pointing to the `User` model.

## 2. Role & Access Control
- **Seeder:** Update `RolesAndPermissionsSeeder` to include the `dispatcher` role.
- **Policies:** Update relevant policies or `canAccessPanel()` checks so that the `dispatcher` role can access the `/dispatcher` portal.

## 3. Admin Panel Updates (User Management)
- **UserResource (Admin Panel):**
  - Modify the User form to include a `Select::make('managedPartners')` field.
  - Make this field conditionally visible only when the `dispatcher` role is selected.
  - Use the `relationship('managedPartners', 'company_name')` with `multiple()`.

## 4. Dispatcher Portal (`/dispatcher`)
Create a new Filament Panel Provider `DispatcherPanelProvider`:
- Path: `/dispatcher`
- Role guard: `dispatcher`
- **Navigation Items:**
  - Dashboard
  - Bookings
  - Dispatches
  - Dispatching Report
  - Transport (Companies, Vehicles, Drivers - View Only)
  - Profile

## 5. Portal Resources & Scoping
### A. Booking Resource
- Scope `getEloquentQuery()`: Only show bookings where the `partner_id` is in the dispatcher's `managedPartners` list.
  ```php
  public static function getEloquentQuery(): Builder
  {
      $managedPartnerIds = auth()->user()->managedPartners()->pluck('partners.id');
      return parent::getEloquentQuery()->whereIn('partner_id', $managedPartnerIds);
  }
  ```
- Allowed Actions: View, Edit.

### B. Dispatch Resource
- Allows the dispatcher to manage dispatches (create, edit, view, delete).
- Same capabilities as admin/manager for managing the actual dispatch logistics.

### C. Transport Entities (Read-Only)
- **TransportCompanyResource**, **VehicleResource**, **DriverResource**.
- Implement view-only access (similar to Manager portal).
  - Override `canCreate`, `canEdit`, `canDelete` to return `false`.
  - Use `ViewAction` instead of `EditAction` in tables.
  - Hide create headers in `List` pages.

## 6. Dispatching Report
- Create a dedicated Filament Page or Resource (e.g., `DispatchReport`).
- **Features:**
  - Interacts with a table displaying dispatch data.
  - Filters: Date Range filter, Status filter, Partner filter (limited to managed partners).
  - Export: Bulk action to Export to CSV (using `Maatwebsite\Excel` as implemented in other financial reports).
  - Data points: Dispatch date, Bookings assigned, Drivers, Vehicles, Pickup times.

## Implementation Steps (Tasks)
1. **[Backend]** Create `CreateDispatcherPartnerTable` migration and run it.
2. **[Backend]** Add `managedPartners` to `User` model and `dispatchers` to `Partner` model.
3. **[Backend]** Update `RolesAndPermissionsSeeder` and run it.
4. **[Admin UI]** Update `UserResource` in the Admin panel to handle the `managedPartners` relationship assignment.
5. **[Portal Setup]** Run `php artisan make:filament-panel dispatcher` and configure `DispatcherPanelProvider`.
6. **[Portal Resources]** Duplicate/Configure `BookingResource` and `DispatchResource` for the Dispatcher portal with proper scoping.
7. **[Portal Resources]** Configure read-only `TransportCompanyResource`, `VehicleResource`, and `DriverResource`.
8. **[Reporting]** Build the `DispatchingReport` custom page with filtering and CSV export.
9. **[UI Polish]** Test portal access, relationship scoping, and export functionalities.
