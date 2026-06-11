# 🪂 Adventure Balloon (Booklix) — Operations & Portal Manual

Welcome to the official Operations and Portal Manual for the **Adventure Balloon** (formerly Booklix) Customer Relationship Management (CRM), Booking, and Dispatch platform. 

This document serves as a comprehensive, end-to-end user manual enabling administrators, managers, accountants, partners, and field staff to understand, navigate, and operate the platform efficiently.

---

## 📌 Table of Contents
1. [System Overview & Architecture](#1-system-overview--architecture)
2. [User Authentication & Profile Management](#2-user-authentication--profile-management)
3. [User & Access Control (Roles & Permissions)](#3-user--access-control-roles--permissions)
4. [Booking Lifecycle Management](#4-booking-lifecycle-management)
5. [Dispatch & Logistics Operations](#5-dispatch--logistics-operations)
6. [Partner Management & Invoicing](#6-partner-management--invoicing)
7. [Recycle Bin & Deletion Records](#7-recycle-bin--deletion-records)
8. [Portal-by-Portal Reference Directory](#8-portal-by-portal-reference-directory)

---

## 1. System Overview & Architecture

Adventure Balloon is an enterprise booking, CRM, dispatch, and finance management platform built using **Laravel 12**, **Filament v4**, and **MySQL 8**. The application utilizes Spatie packages to manage role-based access control, DB-stored configurations, activity logging, and media file uploads.

### Core Portals
The platform hosts **10 independent portal panels** designed to support specialized operational roles:

| Portal URL | Panel Name | Associated Role(s) | Primary Purpose |
| :--- | :--- | :--- | :--- |
| `/admin` | Admin Portal | `super_admin`, `admin` | Overall configuration, system settings, user audits, full operations. |
| `/manager` | Manager Portal | `manager` | Operational oversight; full CRUD on bookings/dispatches, read-only on catalogs. |
| `/accountant` | Finance Portal | `accountant` | Invoicing, payment receipts tracking, accounts statements, accountant reports. |
| `/partner` | Partner Portal | `partner` | Self-service passenger bookings, vouchers, monthly invoices statements. |
| `/transport` | Transport Portal | `transport` | Fleet vehicle listing, driver rosters, dispatch manifest details. |
| `/driver` | Driver Portal | `driver` | Mobile-friendly driver schedule, pick-up times, passenger manifest, route map links. |
| `/greeter` | Greeter Portal | `greeter` | Passenger check-in, real-time attendance logging (Show / No-Show). |
| `/guide` | Guide Portal | `guide` | Pilot schedules, flight day rosters, guides passenger list. |
| `/dispatcher` | Dispatcher Portal | `dispatcher` | Intermediate ground dispatching, driver assignments for managed partners. |
| `/balloon-dispatcher`| Balloon Dispatcher | `balloon_dispatcher` | Daily balloon flight manifests, pilot logging, weather notes. |

---

## 2. User Authentication & Profile Management

### 2.1 Accessing the Portals
1. Navigate to the specific URL corresponding to your assigned role (e.g., `http://yourdomain.com/manager`).
2. If you are not authenticated, you will be redirected to the **Universal Login** page for that panel.
3. Enter your registered email address and password, then click **Sign In**.

### 2.2 Account Status Locks
- **Active Status**: Only users with the `is_active` attribute set to `true` (checked/active account) can log in.
- **Access Blocks**: If an administrator deactivates your account, your session will be instantly invalidated upon the next request, and login attempts will return an authorization failure.

### 2.3 Updating Your Profile ("My Profile")
Every authenticated user can manage their personal details by navigating to the **My Profile** page (accessible via the user avatar menu in the top-right corner, or the sidebar link):
- **Full Name**: Modify your display name.
- **Phone Number**: Change your contact details. Keep this updated to ensure WhatsApp dispatch notifications reach you.
- **Login Email**: For security compliance, email addresses are read-only for general staff. Contact a system administrator in the Admin Portal to alter your login email.
- **Password Updates**:
  - Enter a **New Password** (minimum of 8 characters, conforming to strict security standards).
  - Re-type in **Confirm New Password** (must match the new password).
  - Submit by clicking **Save Changes**.

---

## 3. User & Access Control (Roles & Permissions)

The platform utilizes a Role-Based Access Control (RBAC) schema. Roles are defined programmatically and synchronized to permissions in the database via seeders.

### 3.1 User Roles Matrix
1. **`super_admin`**: Full system permissions, including application settings modification (SMTP, Twilio keys, daily passenger capacity, etc.).
2. **`admin`**: Full operational permissions except for modifying systemic infrastructure configurations or deleting other administrative roles.
3. **`manager`**: Operational controller. Performs full CRUD actions on bookings and dispatches, but holds read-only access to partner listings, product guides, and fleet directories.
4. **`accountant`**: Financial manager. Handles invoice generation, records wire transfers, verifies cash logs, and exports accounting reports.
5. **`greeter`**: Ground operations. Manages the passenger attendance register on the flight day.
6. **`transport`**: External fleet owner. Controls their company vehicles list, logs driver accounts, and accesses transport bills.
7. **`driver`**: Ground pickup provider. Views their mobile-optimized schedules.
8. **`partner`**: Third-party booking agency. Creates and views their bookings and checks account invoices.
9. **`guide`**: Flight pilot. Views flight-day passenger lists and pilot manifests.
10. **`dispatcher`**: Assigned partner booking coordinator. Manages dispatches for partners under their care.
11. **`balloon_dispatcher`**: Balloon operations specialist. Registers daily flight logs, pilots, and weather conditions.

### 3.2 Onboarding Users & Profile Associations
When creating user accounts in the Admin Portal under **User Management**, administrators must link the user record to the corresponding profile model depending on their role:
- **Partner users**: Must be linked to a **Partner Company** record.
- **Transport users**: Must be linked to a **Transport Company** record.
- **Driver users**: Must be linked to a **Driver** record.
- **Guide users**: Must be linked to a **Guide** record.

> [!WARNING]
> Failing to associate a user account with its corresponding profile record will cause access blocks. For example, a partner user without a linked Partner Company will receive an access error when attempting to log in to `/partner`.

---

## 4. Booking Lifecycle Management

Adventure Balloon uses a **Unified Booking Engine** tracking two booking streams: **Regular** (created by admins or managers) and **Partner** (submitted directly by agencies).

```
[Booking Created] ──► Status: PENDING (Unconfirmed)
       │
       ▼ (Manager/Admin Confirms Booking)
[Booking Confirmed] ──► Triggers Transport Dispatch
       │
       ▼ (Flight Day: Greeter Marks Attendance)
[Attendance Logged] (Show / No-Show)
       │
       ▼ (Accountant Verifies Payment)
[Payment Logged] (Paid / Partial / Due)
       │
       ▼ (Flight Finished & Fully Paid)
[Booking Completed]
```

### 4.1 Regular Booking Step-by-Step Wizard (Admin/Manager)
Regular bookings are registered using a structured **5-Step Wizard**:
1. **Flight Details**: Select the experience product, flight date, preferred time slot, and passenger count. The system runs a real-time daily PAX capacity validation.
2. **Passenger Details**: Enter passenger records via a repeater (Full Name, Nationality, Type [Adult/Child], Passport No, Weight, Date of Birth). One passenger must be marked as the **Primary Contact** and require an active phone number.
3. **Pricing & Discounts**: The system calculates pricing based on the selected product's base rates. Apply custom discount percentages or flat rates if necessary.
4. **Payment Status**: Log the initial payment method (Cash, Wire, Online) and the amount paid, calculating the remaining balance due.
5. **Review & Confirm**: Check the layout summary and click **Submit** to finalize the transaction.

### 4.2 Partner Self-Service Booking Wizard (Partner Portal)
Partners log bookings through a simplified **3-Step Wizard**:
1. **Flight Details**: Select from products authorized for their agency, flight date (checks daily capacity), passenger counts, and input their internal **Voucher / Booking Reference**.
2. **Passengers**: Renders a passenger list repeater to log names, nationalities, weights, and primary contact number.
3. **Review & Submit**: Displays the pre-calculated final amount based on the partner's custom agreed rates. Upon submission, an email alert is sent to administrators.

### 4.3 Daily Passenger (PAX) Capacity check
To ensure aviation limits are respected, the system enforces a global **250 PAX per day limit** (customizable via Settings). 
- When booking or rescheduling, the platform calculates the total passenger count from all active bookings (`pending` or `confirmed`) for the target date.
- If the new booking exceeds the remaining slot count, the form prevents submission, displaying an warning indicating the available capacity remaining.

---

## 5. Dispatch & Logistics Operations

When bookings are confirmed, managers and dispatchers coordinate passenger transfers from hotels to the launch site.

### 5.1 Fleet & Roster Setup
Before generating dispatches:
- **Transporters** must register their fleet **Vehicles** (including license plate, brand, and seating capacity) and **Drivers** (name, email, phone, and license expiration).
- Drivers are automatically provisioned driver portal accounts upon creation.

### 5.2 Creating a Dispatch
Managers/Admins select a confirmed booking and initiate a **Dispatch Log**:
1. Select the **Transport Company** responsible for the transfer.
2. **Assign Drivers & Vehicles**: Renders a list of drivers linked to that transporter. The system offers an **auto-allocation helper** suggesting the number of drivers needed by dividing total passengers by vehicle seating capacities.
3. Define **Logistics**: Pick-up time, hotel location, and return instructions.

### 5.3 Dispatch Status Transitions
- `pending`: Log created; logistics planning stage.
- `confirmed`: Transport company notified. Triggers an automatic **HTML Email Manifest** to the transport operator.
- `in_progress`: Driver is currently picking up passengers.
- `delivered`: Passengers successfully transferred to launch/return site.
- `cancelled`: Transfer cancelled.

### 5.4 Driver WhatsApp Broadcasts
Inside the dispatch details view page, dispatchers can click **Send WhatsApp to Drivers**:
- Opens a modal listing the assigned drivers with click-to-chat links.
- Clicking a link redirects to `wa.me` with a pre-formatted template containing the dispatch date, pick-up time, hotel location, passenger count, and Google Maps pin link, dispatching details straight to the driver's phone.

---

## 6. Partner Management & Invoicing

Adventure Balloon tracks partner interactions and supports an end-to-end billing cycle.

### 6.1 Partner Company Onboarding
- Administrators register new partner agencies. Partners begin in a `pending` status.
- Once credentials, KYC documents (business license, tax records), and banking details are verified, administrators mark the partner as `approved` and link a portal user account.
- **Product Override Rates**: For each partner, administrators define contract pricing overrides within the partner profile. When a partner user books a flight, the system bypasses base rates and uses the custom negotiated rates.

### 6.2 Invoicing Cycle
Accountants run monthly invoicing cycles using the **Partner Invoice Resource**:
1. Select the target **Partner Company**.
2. Renders the **Invoicing Basket**, listing all confirmed bookings that have not yet been invoiced.
3. Select the bookings to include, set the tax rate, and click **Generate Invoice**.
4. The system:
   - Generates an `INV-YEAR-SEQUENCE` invoice record.
   - Attaches the selected bookings to the invoice, marking them as invoiced to prevent double-billing.
   - Generates a PDF invoice statement.
   - Automatically emails the invoice statement with the PDF attachment to the partner.
5. The invoice status is tracked: `draft` → `sent` → `paid` (upon recording the banking reference code).

---

## 7. Recycle Bin & Deletion Records

To safeguard operational records against accidental loss, the platform implements **Soft Deletes** on all core models.

### 7.1 Deletion Tracking (`deleted_by`)
- When a user deletes a record (e.g. Booking, Partner, Driver, Guide, Product), the record is hidden from operational lists rather than purged from the database.
- The system automatically captures the **Timestamp of deletion** and the **ID of the user** who performed the deletion.

### 7.2 Accessing the Recycle Bin
Under the Admin Portal, administrators can navigate to the **Deletion Records** navigation group:
- Each core resource has a corresponding recycle bin list.
- **Recovery**: Administrators can click the **Restore** action on any record to instantly return it to active operations.
- **Hard Purging**: Only users with the `super_admin` role can see the **Force Delete** button, which permanently erases the record from the database.

---

## 8. Portal-by-Portal Reference Directory

### 8.1 Admin Portal (`/admin`)
- **Primary Users**: Super Admins and Admins.
- **Key Modules**: System Settings (SMTP, Twilio keys, PAX configurations), User Control & Auditing, Partner Onboarding, Fleet oversight, Global Booking lists, and the Recycle Bin (Deletion Records).
- **Restrictions**: General Admins cannot access base system settings or force-delete database entries.

### 8.2 Manager Portal (`/manager`)
- **Primary Users**: Operational Managers.
- **Key Modules**: Full CRUD access on Bookings, Dispatch manifests, and Driver assignments. Renders the daily PAX widgets.
- **Restrictions**: Read-only access to catalogs (Partners, Products, Transport Companies). No access to global settings, billing invoices, or recycle bins.

### 8.3 Accountant Portal (`/accountant`)
- **Primary Users**: Financial Staff.
- **Key Modules**: Booking lists, Payment logs, Invoicing cycles, Accountant reports, and Transporter billings.
- **Restrictions**: Cannot create or modify bookings directly, register new partner profiles, or configure flight logistics.

### 8.4 Partner Portal (`/partner`)
- **Primary Users**: External booking agencies.
- **Key Modules**: My Bookings, self-service booking step wizard, Vouchers download, and Monthly Account Statements.
- **Restrictions**: Can only access, view, and book products authorized by administrators, and cannot view other partners' bookings or operational logistics.

### 8.5 Transport Portal (`/transport`)
- **Primary Users**: Logistics company owners.
- **Key Modules**: Vehicle list management, Driver roster creation, and Dispatch manifests.
- **Restrictions**: Access is strictly scoped to vehicles, drivers, and dispatches associated with their specific transport company.

### 8.6 Driver Portal (`/driver`)
- **Primary Users**: Shuttle drivers.
- **Key Modules**: Mobile-first itinerary, pickup times list, passenger count, and hotel map links.
- **Restrictions**: Read-only portal. Drivers can only view dispatches assigned to them for the current day.

### 8.7 Greeter Portal (`/greeter`)
- **Primary Users**: Check-in staff.
- **Key Modules**: Today's bookings index, passenger lists, and check-in switches (Show / No-Show).
- **Restrictions**: No access to pricing, finance logs, logistics manifests, or user configurations.

### 8.8 Guide Portal (`/guide`)
- **Primary Users**: Pilots/Tour guides.
- **Key Modules**: Personal flight schedule, passenger list check, and flight manifest detail views.
- **Restrictions**: Read-only portal restricted to guide-specific bookings.

### 8.9 Dispatcher Portal (`/dispatcher`)
- **Primary Users**: Ground coordinators.
- **Key Modules**: Intermediate dispatch assignment lists and partner booking schedules.
- **Restrictions**: Scoped strictly to bookings created by partners they are designated to manage.

### 8.10 Balloon Dispatcher Portal (`/balloon-dispatcher`)
- **Primary Users**: Flight dispatchers.
- **Key Modules**: Daily balloon operational logs, pilot flight hour counts, manifest lists, and weather journals.
- **Restrictions**: Scoped to balloon dispatches only; cannot view pricing, financial accounts, or transport logistics.
