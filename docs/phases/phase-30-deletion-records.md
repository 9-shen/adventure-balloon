# Phase 30 — Deletion Records & Recycle Bin

## Objective

Implement a centralized "Recycle Bin" (Deletion Records) system across the application. It will provide a dedicated area at the bottom of the Admin navigation to view, restore, and permanently delete soft-deleted records for all major entities. Crucially, it must track and display _who_ deleted the record for safety and accountability.

## 1. Database & Tracking Changes

Laravel's standard `SoftDeletes` only records `deleted_at`. To track _who_ deleted a record efficiently:

- **Migration**: Create a single migration `add_deleted_by_to_models` that adds a nullable `deleted_by` foreign key (referencing the `users` table) to the following tables:
    - `bookings`
    - `dispatches`
    - `partners`
    - `transport_companies`
    - `users`
    - `vehicles`
    - `drivers`
    - `guides`
    - `products`
- **Model Trait (`TracksDeletedBy`)**: Create a custom trait that extends/hooks into the model's `deleting` event. When a user deletes a record, the trait will automatically capture `Auth::id()` and save it to the `deleted_by` column.

## 2. Navigation & Access

- Add a new Navigation Group named **"Deletion Records"**.
- This group should be placed at the very bottom of the Admin panel sidebar.
- Restrict access to this group to `super_admin` only (and optionally `admin`).

## 3. Filament Resources

Instead of just relying on the `TrashedFilter` inside the active resource pages, we will create dedicated list pages under the "Deletion Records" group.

For each entity (Booking, Dispatch, Partner, etc.):

- Create a corresponding `Deleted{Entity}Resource` (e.g., `DeletedBookingResource`).
- **Query Modification**: The resource will override `getEloquentQuery()` to return `onlyTrashed()`.
- **Table Columns**:
    - Primary identifier (Ref, Name, etc.)
    - `deleted_at` (Date & Time)
    - **`deletedBy.name`** (The user who performed the deletion)
- **Row Actions**:
    - `RestoreAction`
    - `ForceDeleteAction` (Permanent Delete)
- **Bulk Actions**:
    - `RestoreBulkAction`
    - `ForceDeleteBulkAction`
- Disable `Create`, `Edit`, and normal `View` actions to keep it strictly as a recycle bin management interface.

## 4. Implementation Steps

1. Create the `add_deleted_by` migration and run it.
2. Create the `TracksDeletedBy` trait.
3. Apply the trait and the `deletedBy()` BelongsTo relationship to all target models.
4. Generate the `Deleted*Resource` Filament files.
5. Configure the columns, actions, and navigation group for each.
6. **Force Delete Cascading**: Handled `Integrity constraint violation` by adding `booted()` and `forceDeleting` model events to `Dispatch` and `Booking` to cleanly delete `dispatch_drivers` pivot records and `transport_bill_items` before force-deleting a booking or dispatch.
