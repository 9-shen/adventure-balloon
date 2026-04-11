# 🚀 Booklix — Installation & Setup Guide

> Step-by-step instructions to clone and run the Booklix project on a new machine.

---

## 📋 Prerequisites

Make sure the following are installed on the target machine **before** you begin:

| Requirement | Version | Download |
|---|---|---|
| **PHP** | `^8.2` | https://www.php.net/downloads |
| **Composer** | Latest | https://getcomposer.org/download |
| **Node.js & npm** | LTS (v18+) | https://nodejs.org |
| **MySQL** | 8.0+ | https://dev.mysql.com/downloads |
| **Git** | Latest | https://git-scm.com/downloads |

> **Windows tip:** Use [XAMPP](https://www.apachefriends.org/) or [Laragon](https://laragon.org/) to get PHP + MySQL in one installer. Laragon is highly recommended — it also sets up virtual hosts automatically.

### Required PHP Extensions

Ensure these PHP extensions are enabled in your `php.ini`:

```
extension=pdo_mysql
extension=mbstring
extension=openssl
extension=tokenizer
extension=xml
extension=ctype
extension=json
extension=bcmath
extension=fileinfo
extension=gd
extension=zip
extension=intl
extension=exif
```

---

## 1️⃣ Clone the Repository

```bash
git clone https://github.com/9-shen/adventure-balloon.git booklix-app
cd booklix-app
```

---

## 2️⃣ Install PHP Dependencies

```bash
composer install
```

---

## 3️⃣ Install Node Dependencies

```bash
npm install
```

---

## 4️⃣ Configure the Environment

Copy the example environment file and generate a fresh app key:

```bash
cp .env.example .env
php artisan key:generate
```

Now open `.env` in a text editor and update the following values to match your local setup:

```env
APP_NAME=Booklix
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booklix_app      # ← create this database first (see step 5)
DB_USERNAME=root
DB_PASSWORD=                 # ← your MySQL root password
```

> **Note:** Leave `MAIL_MAILER=log` for local development. Emails will be written to `storage/logs/laravel.log` instead of being sent.

---

## 5️⃣ Create the Database

Open your MySQL client (phpMyAdmin, TablePlus, MySQL Workbench, or command line) and create the database:

```sql
CREATE DATABASE `booklix_app` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Or via the command line:

```bash
mysql -u root -p -e "CREATE DATABASE booklix_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

---

## 6️⃣ Run Migrations & Seed the Database

```bash
php artisan migrate --seed
```

This will:
- Create all database tables
- Seed roles & permissions (Spatie)
- Create the default **super admin** user

> **Default super admin credentials:**
> - Email: `admin@booklix.com`
> - Password: `password`
>
> ⚠️ **Change the password immediately** after first login in production.

---

## 7️⃣ Set Up Storage

Link the public storage disk so uploaded files (avatars, PDFs, etc.) are accessible:

```bash
php artisan storage:link
```

---

## 8️⃣ Build Frontend Assets

```bash
npm run build
```

> For active development with hot-reload, use `npm run dev` instead (see Development Mode below).

---

## 9️⃣ Clear All Caches

```bash
php artisan optimize:clear
```

---

## 🏃 Running the App

### Quick Start (single terminal)

```bash
php artisan serve
```

Then visit: **http://127.0.0.1:8000**

---

### Development Mode (recommended — runs all services in parallel)

```bash
composer run dev
```

This starts **4 processes** concurrently:
- `php artisan serve` — Laravel HTTP server
- `php artisan queue:listen` — Queue worker (for jobs/notifications)
- `php artisan pail` — Log viewer
- `npm run dev` — Vite HMR for frontend assets

---

## 🔐 Admin Panels

| Panel | URL | Role Required |
|---|---|---|
| **Admin** | `/admin` | `super_admin`, `admin`, `manager`, etc. |
| **Partner** | `/partner` | `partner` (with `partner_id` linked) |
| **Transport** | `/transport` | `transport` (with `transport_company_id` linked) |
| **Driver** | `/driver` | `driver` (with `driver_id` linked) |
| **Greeter** | `/greeter` | `greeter` |

---

## 🛠️ Troubleshooting

### `rename(...): Access is denied` (Windows)
Laravel's Blade compiler cannot write compiled view files. Run:
```bash
php artisan view:clear
php artisan optimize:clear
```
If the error persists, **exclude the project folder from Windows Defender / antivirus** real-time scanning.

### `php_network_getaddresses: getaddrinfo failed`
Your `DB_HOST` is wrong or MySQL is not running. Verify MySQL service is started.

### Composer install fails with PHP version error
Check your PHP version: `php -v`. It must be **8.2 or higher**.

### `Class "..." not found` after pulling new code
Run:
```bash
composer dump-autoload
php artisan optimize:clear
```

### Migrations fail with `Table already exists`
Your database has stale tables. Either drop and recreate the database, or run:
```bash
php artisan migrate:fresh --seed
```
> ⚠️ This **wipes all data**. Only use on a fresh install.

### Media / avatar images not showing
Make sure you ran `php artisan storage:link`. Check that `storage/app/public` is symlinked to `public/storage`.

---

## 📦 Tech Stack Reference

| Layer | Technology |
|---|---|
| Backend | Laravel 12 |
| Admin UI | Filament 4 |
| Roles & Permissions | Spatie Laravel Permission |
| Media | Spatie Laravel Media Library |
| Settings | Spatie Laravel Settings |
| PDF | barryvdh/laravel-dompdf |
| Excel Export | Maatwebsite Excel |
| Activity Log | Spatie Laravel Activitylog |
| Frontend | Vite + Tailwind (via Filament) |
| Queue | Database driver |

---

## 📁 Key Directories

```
booklix-app/
├── app/
│   ├── Filament/
│   │   ├── Admin/          ← Admin panel resources & pages
│   │   ├── Partner/        ← Partner portal
│   │   ├── Transport/      ← Transport portal
│   │   ├── Driver/         ← Driver portal
│   │   └── Greeter/        ← Greeter portal
│   ├── Models/             ← Eloquent models
│   └── Providers/
│       └── Filament/       ← Panel providers (one per panel)
├── database/
│   ├── migrations/         ← All DB migrations
│   └── seeders/            ← Role/permission + demo data seeders
├── docs/                   ← Project documentation & phase specs
└── storage/
    └── app/public/         ← Uploaded files (symlinked to public/storage)
```

---

*Last updated: April 2026 — Booklix v1.0 (Phase 18)*
