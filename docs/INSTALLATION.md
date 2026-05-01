# 🚀 Adventure Balloon (Booklix) — Installation & Deployment Guide

> Step-by-step instructions for both **local development** and **production deployment** on Contabo VPS with Coolify.

---

## 📑 Table of Contents

- [Local Development Setup](#-local-development-setup)
- [Production Deployment — Contabo VPS + Coolify](#-production-deployment--contabo-vps--coolify)

---

# 💻 Local Development Setup

## 📋 Prerequisites

Make sure the following are installed on your machine **before** you begin:

| Requirement | Version | Download |
|---|---|---|
| **PHP** | `^8.3` | https://www.php.net/downloads |
| **Composer** | Latest | https://getcomposer.org/download |
| **Node.js & npm** | LTS (v22+) | https://nodejs.org |
| **MySQL** | 8.0+ | https://dev.mysql.com/downloads |
| **Git** | Latest | https://git-scm.com/downloads |

> **Windows tip:** Use [Laragon](https://laragon.org/) to get PHP + MySQL + Git in one installer. It also sets up virtual hosts automatically and is highly recommended.

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
extension=redis      ← optional for local dev (can use database driver instead)
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
APP_NAME="Adventure Balloon"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=booklix_app      # ← create this database first (see step 5)
DB_USERNAME=root
DB_PASSWORD=                 # ← your MySQL root password

# For local dev, use database queues (no Redis needed)
CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
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

> For active development with hot-reload, use `npm run dev` instead.

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
| **Admin** | `/admin` | `super_admin`, `admin`, `accountant` |
| **Manager** | `/manager` | `manager` |
| **Partner** | `/partner` | `partner` (with `partner_id` linked) |
| **Transport** | `/transport` | `transport` (with `transport_company_id` linked) |
| **Driver** | `/driver` | `driver` (with `driver_id` linked) |
| **Greeter** | `/greeter` | `greeter` |
| **Guide** | `/guide` | `guide` |
| **Dispatcher** | `/dispatcher` | `dispatcher` |

---

---

# ☁️ Production Deployment — Contabo VPS + Coolify

This section covers deploying the application on a **Contabo VPS** managed by **Coolify**, using Docker (multi-stage build).

## 📋 Prerequisites

Before you start, make sure you have:

| Requirement | Details |
|---|---|
| **Contabo VPS** | Ubuntu 24.04 LTS (recommended) |
| **Coolify** | v4.x installed on the VPS |
| **Domain** | DNS A record pointing to VPS IP |
| **GitHub repo** | `9-shen/adventure-balloon` (private or public) |
| **GitHub Token** | Personal Access Token with `repo` scope |

---

## Step 1 — Install Coolify on Your VPS

SSH into your Contabo VPS as root:

```bash
ssh root@YOUR_VPS_IP
```

Run the official Coolify one-line installer:

```bash
curl -fsSL https://cdn.coollabs.io/coolify/install.sh | bash
```

Once complete, access the Coolify dashboard at:
```
http://YOUR_VPS_IP:8000
```

Complete the initial setup wizard (create your admin account).

---

## Step 2 — Connect Your GitHub Repository

1. In Coolify, go to **Settings → Sources**
2. Click **Add → GitHub App** (recommended) or **GitHub with PAT**
3. Follow the OAuth flow to authorize Coolify on your GitHub account
4. Make sure the `9-shen/adventure-balloon` repository is accessible

---

## Step 3 — Create Required Services

Before adding the app, provision the databases it depends on.

### 3A — Create MySQL Database

1. Go to **Servers → localhost → New Resource → Database → MySQL**
2. Configure:
   - **Name:** `booklix-mysql`
   - **MySQL Database:** `booklix`
   - **MySQL User:** `booklix`
   - **MySQL Password:** *(generate a strong password — save it!)*
   - **MySQL Root Password:** *(generate separately — save it!)*
3. Click **Save** and then **Start**
4. Note the **internal hostname** (e.g., `booklix-mysql`) — you'll use this as `DB_HOST`

### 3B — Create Redis

1. Go to **Servers → localhost → New Resource → Database → Redis**
2. Configure:
   - **Name:** `booklix-redis`
   - **Redis Password:** *(generate a strong password — save it!)*
3. Click **Save** and then **Start**
4. Note the **internal hostname** (e.g., `booklix-redis`) — you'll use this as `REDIS_HOST`

---

## Step 4 — Create the Application

1. Go to **Projects → New Project** → name it `Adventure Balloon`
2. Inside the project, click **New Resource → Application**
3. Select **GitHub** as the source
4. Choose the `9-shen/adventure-balloon` repository and `main` branch
5. Coolify will detect your `Dockerfile` — confirm **Dockerfile** as the build type
6. Set the **Port** to `80`

---

## Step 5 — Configure Environment Variables

In the application's **Environment Variables** tab, add the following.

> ⚠️ Mark sensitive variables (passwords, keys) as **Secret** in Coolify so they are not exposed in logs.

### Application

```env
APP_NAME="Adventure Balloon"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=                         # ← generate with: php artisan key:generate --show
LOG_CHANNEL=stderr
LOG_LEVEL=error
TRUSTED_PROXIES=*
```

### Database

```env
DB_CONNECTION=mysql
DB_HOST=booklix-mysql            # ← internal Coolify service hostname
DB_PORT=3306
DB_DATABASE=booklix
DB_USERNAME=booklix
DB_PASSWORD=YOUR_MYSQL_PASSWORD  # ← from Step 3A (mark as Secret)
```

### Redis (Cache / Session / Queue)

```env
REDIS_HOST=booklix-redis         # ← internal Coolify service hostname
REDIS_PORT=6379
REDIS_PASSWORD=YOUR_REDIS_PASSWORD  # ← from Step 3B (mark as Secret)

CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
QUEUE_CONNECTION=redis
```

### Mail (SMTP)

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your-mail-password  # ← mark as Secret
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your@email.com
MAIL_FROM_NAME="Adventure Balloon"
```

### Storage

```env
FILESYSTEM_DISK=local
```

> **Note:** All environment variables set in Coolify are injected at **runtime** into the container. The `APP_KEY` is the only one needed at build time (a temporary key is used during the Docker build, the real one is injected at runtime).

---

## Step 6 — Generate APP_KEY

On your **local machine** (or any machine with PHP), run:

```bash
php artisan key:generate --show
```

Copy the output (e.g., `base64:abc123...==`) and paste it as the `APP_KEY` environment variable in Coolify.

---

## Step 7 — Configure the Domain & SSL

1. In the application settings, go to the **Domains** tab
2. Add your domain: `https://yourdomain.com`
3. Coolify will automatically provision a **Let's Encrypt SSL certificate** via Traefik
4. Make sure your domain's **DNS A record** points to your VPS IP before this step

---

## Step 8 — Deploy

1. Click **Deploy** in the Coolify application dashboard
2. Monitor the **Build Logs** — the full build takes approximately **4–6 minutes**:
   - Stage 0: Composer dependencies (~60s)
   - Stage 1: Vite/npm build (~3 min)
   - Stage 2: PHP Debian image + extensions (~60s)
3. Once deployed, the **container startup** runs `start.sh` which:
   - Creates storage directories
   - Clears stale caches
   - Runs `php artisan migrate --force`
   - Runs `php artisan db:seed --force`
   - Links public storage
   - Publishes Filament/Livewire assets
   - Caches config and views
   - Starts Nginx + PHP 8.3-FPM via Supervisor

---

## Step 9 — Verify the Deployment

Once the container is running, test each panel:

| Check | URL |
|---|---|
| Home / App | `https://yourdomain.com` |
| Admin panel | `https://yourdomain.com/admin` |
| Manager panel | `https://yourdomain.com/manager` |
| Partner portal | `https://yourdomain.com/partner` |
| Dispatcher portal | `https://yourdomain.com/dispatcher` |

Login with the seeded super admin credentials:
- **Email:** `admin@booklix.com`
- **Password:** `password`

> ⚠️ **Change the default password immediately** after first login.

---

## Step 10 — Post-Deploy Checklist

- [ ] Change default `admin@booklix.com` password
- [ ] Configure SMTP settings in **Admin → Settings → Email**
- [ ] Configure WhatsApp/notification settings if applicable
- [ ] Set up a custom domain and verify SSL is active (🔒 padlock in browser)
- [ ] Enable Coolify's **Auto-deploy on push** (webhook) for future deploys
- [ ] (Optional) Set up automated backups — see `docs/phases/phase-27-minio-backup.md`

---

## 🔄 Redeployment (After Code Changes)

Every time you push to the `main` branch, Coolify can automatically redeploy. To enable this:

1. In your Coolify application, go to **Settings**
2. Enable **Automatic Deployment** (webhook trigger on push)
3. Coolify will rebuild the Docker image and restart the container

Manual redeploy is also available with one click from the Coolify dashboard.

---

## 🛠️ Troubleshooting — Production

### Container fails to start

Check the **Runtime Logs** in Coolify (not the build logs). Common causes:

- `APP_KEY` is missing or malformed → regenerate and redeploy
- `DB_HOST` unreachable → verify the MySQL service name matches exactly
- `REDIS_HOST` unreachable → verify the Redis service is running

### Build times out (>30 min)

Ensure the `Dockerfile` uses `ubuntu:24.04` and installs PHP via `apt-get`, **not** source compilation. 
- **Alpine images** (`php:fpm-alpine`) compile from source and can take 30+ minutes on slow VPS.
- **Ubuntu images** use pre-built binaries and take ~15 seconds to install extensions.
- **Contabo Tip:** If the build still times out, check "Server Resources" in Coolify. Contabo I/O can be very slow; our `Dockerfile` is optimized with `--chown` to avoid slow recursive permission changes.

### "Unable to find component" / Livewire Errors

- Ensure you are NOT running `php artisan route:cache` in production. Filament and Livewire register routes dynamically.
- Check that `docker/start.sh` is correctly skipping route caching.

### Migrations fail on first deploy

The `start.sh` script runs migrations automatically on every container start (`--force`). If they fail, check:
1. `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` are all correctly set
2. The MySQL service is running in Coolify

### Files / uploads not persisting between deploys

By default, `storage/app/public` lives inside the container and is wiped on each redeploy. To persist uploads:

1. In Coolify, add a **Volume** mount: `/var/www/html/storage/app/public` → a named volume
2. Or use an S3-compatible storage (MinIO) — see `docs/phases/phase-27-minio-backup.md`

---

## 🛠️ Troubleshooting — Local Development

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
| Queue | Redis (production) / Database (local) |
| Cache/Session | Redis (production) / Database (local) |
| Web Server | Nginx + PHP-FPM (Docker, Supervisor) |
| Container | Docker multi-stage build |
| Hosting | Contabo VPS + Coolify |

---

## 📁 Key Directories

```
booklix-app/
├── app/
│   ├── Filament/
│   │   ├── Admin/          ← Admin panel resources & pages
│   │   ├── Manager/        ← Manager portal
│   │   ├── Partner/        ← Partner portal
│   │   ├── Transport/      ← Transport portal
│   │   ├── Driver/         ← Driver portal
│   │   ├── Greeter/        ← Greeter portal
│   │   ├── Guide/          ← Guide portal
│   │   └── Dispatcher/     ← Dispatcher portal
│   ├── Models/             ← Eloquent models
│   └── Providers/
│       └── Filament/       ← Panel providers (one per panel)
├── database/
│   ├── migrations/         ← All DB migrations
│   └── seeders/            ← Role/permission + demo data seeders
├── docker/
│   ├── nginx.conf          ← Nginx server block
│   ├── php.ini             ← PHP runtime config
│   ├── supervisord.conf    ← Supervisor config (nginx + php-fpm)
│   └── start.sh            ← Container bootstrap script
├── docs/                   ← Project documentation & phase specs
└── storage/
    └── app/public/         ← Uploaded files (symlinked to public/storage)
```

---

*Last updated: April 2026 — Adventure Balloon v1.0 (Phase 28)*
