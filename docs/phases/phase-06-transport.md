# Phase 6 — Transport Management
**Status: 🔲 Pending**  
**Priority:** 🟠 MEDIUM-HIGH  
**Can run parallel with:** Phase 5  
**Est. Days:** 4–5

---

## Goal
Manage transport companies, their vehicles, and drivers. The dispatch system (Phase 9) uses this data to assign transport to bookings.

---

## Checklist

### Database
- [ ] `transport_companies` table
- [ ] `vehicles` table
- [ ] `drivers` table
- [ ] `driver_vehicle` pivot table

### Models
- [ ] `TransportCompany` model
- [ ] `Vehicle` model
- [ ] `Driver` model (with `HasMedia` for license docs)
- [ ] Relationships wired up

### Filament Resources
- [ ] `TransportCompanyResource`
- [ ] `VehicleResource` (nested under company or standalone)
- [ ] `DriverResource` with license document upload

---

## Key Schema

```sql
CREATE TABLE transport_companies (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name  VARCHAR(255) NOT NULL,
    contact_name  VARCHAR(255) NULL,
    email         VARCHAR(255) NULL,
    phone         VARCHAR(50)  NULL,
    address       TEXT         NULL,
    bank_name     VARCHAR(255) NULL,
    bank_account  VARCHAR(100) NULL,
    is_active     BOOLEAN      NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NULL,
    updated_at    TIMESTAMP    NULL
);

CREATE TABLE vehicles (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transport_company_id  BIGINT UNSIGNED NOT NULL,
    make                  VARCHAR(100) NOT NULL,
    model                 VARCHAR(100) NOT NULL,
    plate_number          VARCHAR(50)  NOT NULL UNIQUE,
    capacity              INT          NOT NULL,   -- number of passengers
    type                  VARCHAR(100) NULL,       -- e.g. Minibus, Van, Bus
    price_per_trip        DECIMAL(10,2) NULL,
    is_active             BOOLEAN      NOT NULL DEFAULT 1,
    created_at            TIMESTAMP    NULL,
    updated_at            TIMESTAMP    NULL,
    FOREIGN KEY (transport_company_id) REFERENCES transport_companies(id) ON DELETE CASCADE
);

CREATE TABLE drivers (
    id                    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transport_company_id  BIGINT UNSIGNED NOT NULL,
    name                  VARCHAR(255) NOT NULL,
    phone                 VARCHAR(50)  NOT NULL,   -- WhatsApp number for notifications
    national_id           VARCHAR(100) NULL,
    license_number        VARCHAR(100) NULL,
    license_expiry        DATE         NULL,
    is_active             BOOLEAN      NOT NULL DEFAULT 1,
    created_at            TIMESTAMP    NULL,
    updated_at            TIMESTAMP    NULL,
    FOREIGN KEY (transport_company_id) REFERENCES transport_companies(id) ON DELETE CASCADE
);

CREATE TABLE driver_vehicle (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    driver_id   BIGINT UNSIGNED NOT NULL,
    vehicle_id  BIGINT UNSIGNED NOT NULL,
    is_default  BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (driver_id)  REFERENCES drivers(id)  ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
);
```
