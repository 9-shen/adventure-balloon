# 🎈 Booklix — Hot Air Balloon CRM & Booking Platform

A full-featured Laravel 12 + Filament 4 platform for managing hot air balloon bookings, dispatch, drivers, partners, transport, invoicing, and multi-panel administration.

---

## 🏗 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Admin UI | Filament 4 |
| Frontend | Vite 7 + Tailwind CSS 4 |
| Database | MySQL 8 |
| Cache / Sessions | Database (default) or Redis |
| Queue | Database (default) or Redis |
| Storage | Local (S3-compatible optional) |
| Container | Docker (single image: nginx + php-fpm + supervisor) |
| Deployment | Coolify (self-hosted PaaS) |

---

## 📋 Required Environment Variables

Set these in Coolify → your app → **Environment Variables** before deploying.

```env
# ── App ──────────────────────────────────────────────────────────────
APP_NAME=Booklix
APP_ENV=production
APP_KEY=                          # Generate: php artisan key:generate --show
APP_DEBUG=false
APP_URL=https://your-domain.com

# ── Database (MySQL) ──────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=booklix
DB_USERNAME=booklix_user
DB_PASSWORD=your-secure-password

# ── Session & Cache ───────────────────────────────────────────────────
SESSION_DRIVER=database
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database

# ── Redis (optional — for better performance) ─────────────────────────
# SESSION_DRIVER=redis
# CACHE_STORE=redis
# QUEUE_CONNECTION=redis
# REDIS_HOST=your-redis-host
# REDIS_PORT=6379
# REDIS_PASSWORD=null

# ── Mail ──────────────────────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME=Booklix

# ── Filesystem ────────────────────────────────────────────────────────
FILESYSTEM_DISK=local

# ── Proxy (required for Coolify / reverse proxy) ──────────────────────
TRUSTED_PROXIES=*

# ── Logging ───────────────────────────────────────────────────────────
LOG_CHANNEL=stderr
LOG_LEVEL=error
```

> **⚠ Important:** `APP_KEY` must be set before the first deployment or all encrypted data (sessions, passwords) will fail.
> Generate it with: `php artisan key:generate --show` on any local machine.

---

## 🚀 Deploying on Coolify — Step by Step

### Prerequisites
- A Coolify instance running (v4+)
- A MySQL 8 database service created in Coolify (or external)
- Your domain pointed to the Coolify server
- This repository pushed to GitHub

---

### Step 1 — Create a New Resource in Coolify

1. Go to **Coolify Dashboard** → your project → **+ New Resource**
2. Select **Application**
3. Select your **GitHub** source and choose the `adventure-balloon` repository
4. Choose branch: `main`
5. Select **Dockerfile** as the build pack
6. Set **Port**: `80`
7. Click **Continue**

---

### Step 2 — Configure Environment Variables

In Coolify → your app → **Environment Variables**, add **all** variables from the table above.

**Critical ones that MUST be set:**

| Variable | Value |
|---|---|
| `APP_KEY` | Run `php artisan key:generate --show` locally and paste the result |
| `APP_ENV` | `production` |
| `APP_URL` | `https://your-domain.com` (with HTTPS) |
| `DB_HOST` | Your MySQL host (Coolify internal name or IP) |
| `DB_DATABASE` | `booklix` |
| `DB_USERNAME` | your db user |
| `DB_PASSWORD` | your db password |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `TRUSTED_PROXIES` | `*` |
| `LOG_CHANNEL` | `stderr` |

---

### Step 3 — Add Persistent Volume (⚠ Critical)

Without a persistent volume, **all uploaded files are deleted on every redeploy**.

1. In Coolify → your app → **Storages** tab
2. Click **+ Add Volume**
3. Configure as follows:

| Field | Value |
|---|---|
| **Source Path (Volume Name)** | `booklix-storage` |
| **Destination Path (in container)** | `/var/www/html/storage` |

4. Click **Save**

> **Why this path?** The `storage/` directory holds uploaded media, logs, session files (if using `file` driver), and framework cache. Mounting it as a volume persists data across container restarts and redeployments.

> **Note:** `public/storage` is a symlink to `storage/app/public` — this is created automatically by `php artisan storage:link` in the startup script.

---

### Step 4 — Configure Domain

1. In Coolify → your app → **Domains**
2. Add your domain: `https://booklix.your-domain.com`
3. Enable **Force HTTPS**
4. Coolify will auto-provision a Let's Encrypt SSL certificate

---

### Step 5 — Deploy

1. Click **Deploy** in Coolify
2. Watch the **Deployment Log** — it runs in ~3–5 minutes
3. The `start.sh` script runs automatically on every container start:

```
[1/10]  Create storage directories & set permissions
[2/10]  Clear stale caches
[3/10]  Run database migrations
[4/10]  Seed database (roles, admin user, settings)
[5/10]  Link public storage
[6/10]  Publish Livewire JS assets (static files)
[7/10]  Publish Filament assets
[8/10]  Cache configuration
[9/10]  Cache routes
[10/10] Cache views
→ Start Supervisor (nginx + php-fpm + queue + scheduler)
```

---

### Step 6 — Verify Deployment

Visit your domain. You should see the Booklix login page at `/admin/login`.

**Default Admin Credentials** (set in `AdminUserSeeder.php`):

| Field | Value |
|---|---|
| Email | `admin@booklix.com` |
| Password | `password` (change immediately after first login) |

---

### Step 7 — Post-Deploy Checks

In Coolify → your app → **Terminal**, run these to verify everything is healthy:

```bash
# Check all supervisor processes are running
supervisorctl status

# Check Laravel logs
tail -50 /var/www/html/storage/logs/laravel.log

# Test database connection
php artisan tinker --execute="DB::select('SELECT 1');"

# Check migrations ran
php artisan migrate:status

# Check roles were seeded
php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();"

# Verify Livewire assets are published
ls /var/www/html/public/vendor/livewire/
```

---

## 🐛 Troubleshooting

### `livewire.min.js` returns 404

**Cause:** Livewire JS assets weren't published to `public/vendor/livewire/`.

**Fix (in Coolify Terminal):**
```bash
php artisan vendor:publish --tag=livewire:assets --force
```
This is now done automatically in `start.sh` step 6.

---

### Livewire `/update` returns 500

**Cause:** Usually a session or CSRF issue behind the reverse proxy.

**Fix:** Make sure `TRUSTED_PROXIES=*` is set in your Coolify env vars. This is already configured in `bootstrap/app.php`.

---

### "Migration failed" in boot logs

**Cause:** Database credentials wrong, or DB not reachable.

**Fix:**
1. Verify `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in Coolify env vars
2. Make sure the MySQL service is on the same Coolify network as your app
3. In Coolify's MySQL service, note the internal hostname (usually the service name)

---

### White screen / 500 error on all pages

**Cause:** Missing `APP_KEY`.

**Fix:** Generate and set the key:
```bash
php artisan key:generate --show
# Copy the output and set it as APP_KEY in Coolify env vars
```

---

### Roles dropdown shows "Loading..." and never loads

**Cause:** The Spatie Permission `roles` table wasn't seeded, or a session issue.

**Fix (in Coolify Terminal):**
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=AdminUserSeeder --force
```

---

### Queue worker not processing jobs

**Cause:** `QUEUE_CONNECTION` mismatch.

**Fix:** Ensure `QUEUE_CONNECTION=database` in env vars (or `redis` if you have Redis configured). The queue worker reads this env var automatically.

---

### Storage files not accessible (404 on images)

**Fix (in Coolify Terminal):**
```bash
php artisan storage:link --force
```

---

## 📁 Docker Architecture

```
Container (single)
├── nginx          (port 80 → public/ directory)
├── php-fpm        (127.0.0.1:9000)
├── queue-worker   (php artisan queue:work, as www-data)
└── scheduler      (php artisan schedule:run every 60s, as www-data)

All processes managed by Supervisor (/etc/supervisord.conf)
Bootstrap sequence run by /start.sh on container start
```

---

## 🔑 Roles & Portals

| Role | Panel URL | Description |
|---|---|---|
| `super_admin` | `/admin` | Full system access |
| `admin` | `/admin` | Full access except super_admin actions |
| `manager` | `/manager` | Read-only operations view |
| `accountant` | `/accountant` | Invoices & financial reports |
| `greeter` | `/greeter` | Check-in & customer greeting |
| `transport` | `/transport` | Transport dispatch management |
| `driver` | `/driver` | Assigned dispatch view |
| `partner` | `/partner` | Partner bookings & commission |

---

## 💻 Local Development

```bash
# 1. Clone and install
git clone https://github.com/9-shen/adventure-balloon.git
cd adventure-balloon

# 2. Copy env and generate key
cp .env.example .env
php artisan key:generate

# 3. Install dependencies
composer install
npm install

# 4. Setup database (SQLite by default for local dev)
php artisan migrate --force
php artisan db:seed

# 5. Run dev server (starts all services)
composer dev
```

The `composer dev` command starts: Laravel server, queue worker, log viewer (Pail), and Vite — all concurrently.

---

## 📦 Project Structure

```
app/
├── Filament/
│   ├── Admin/          # Super Admin & Admin panel
│   ├── Manager/        # Manager read-only panel
│   ├── Accountant/     # Accountant financial panel
│   ├── Greeter/        # Greeter check-in panel
│   ├── Transport/      # Transport dispatch panel
│   ├── Driver/         # Driver dispatch panel
│   └── Partner/        # Partner booking panel
├── Models/             # Eloquent models
├── Providers/          # Service providers
└── Services/           # Business logic (InvoiceService, etc.)

docker/
├── nginx.conf          # Nginx virtual host config
├── php.ini             # PHP production settings
├── supervisord.conf    # Process manager config
└── start.sh            # Container bootstrap script

database/
├── migrations/         # 37 migration files
└── seeders/
    ├── RolesAndPermissionsSeeder.php   # 8 roles + permissions
    ├── AdminUserSeeder.php              # Default admin user
    └── SettingsSeeder.php              # App default settings
```
