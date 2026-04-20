# 🎈 Booklix — CRM + Booking + Dispatch Platform

> **Stack:** Laravel 12 · Filament 4 · MySQL 8 · Redis · PHP 8.2+
> **Deployment:** Coolify (self-hosted PaaS)

---

## 📋 Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Create Services in Coolify](#2-create-services-in-coolify)
3. [Create the Application](#3-create-the-application)
4. [Environment Variables](#4-environment-variables)
5. [Connect MySQL](#5-connect-mysql)
6. [Connect Redis](#6-connect-redis)
7. [Build & Deploy](#7-build--deploy)
8. [Post-Deploy Commands](#8-post-deploy-commands)
9. [Storage Link & File Uploads](#9-storage-link--file-uploads)
10. [Run Migrations & Seeders](#10-run-migrations--seeders)
11. [Queue Worker (Background Jobs)](#11-queue-worker-background-jobs)
12. [Scheduler (Cron)](#12-scheduler-cron)
13. [First Login](#13-first-login)
14. [Troubleshooting](#14-troubleshooting)

---

## 1. Prerequisites

Before starting, make sure you have:

- A running **Coolify** instance (v4+)
- A **GitHub** repository with this codebase
- A domain name (or use Coolify's auto-generated URL)
- SSH access to your Coolify server (optional but useful)

---

## 2. Create Services in Coolify

You need **two services** before creating the app: MySQL and Redis.

### 2.1 — Create MySQL 8 Database

1. In Coolify, go to **Resources → New Resource**
2. Select **Database → MySQL**
3. Choose version **8.0**
4. Set:
   - **Database Name:** `booklix`
   - **Username:** `booklix_user`
   - **Password:** *(generate a strong password and save it)*
5. Click **Create** and wait for it to start
6. Once running, copy the **Internal Connection String** — you'll need it in Step 4

> ⚠️ Use the **internal hostname** (not the public one) when connecting from the app container. Coolify shows this as something like `mysql-booklix.internal` or gives you individual host/port values.

### 2.2 — Create Redis

1. Go to **Resources → New Resource**
2. Select **Database → Redis**
3. Set a **password** (save it)
4. Click **Create** and wait for it to start
5. Copy the **internal hostname** and **password**

---

## 3. Create the Application

1. Go to **Resources → New Resource → Application**
2. Select **GitHub** and connect your repository
3. Select the repository: `adventure-balloon` (or your fork)
4. Select branch: `main`
5. Set **Build Pack** to: `nixpacks` or `Dockerfile` (nixpacks is recommended)
6. Set **Port** to: `8000`
7. Click **Continue**

---

## 4. Environment Variables

In the Coolify application settings, go to **Environment Variables** and add the following.

> 💡 In Coolify, you can paste all variables at once using the "Paste as .env" button.

```env
# ─── App ──────────────────────────────────────────────────────────
APP_NAME=Booklix
APP_ENV=production
APP_DEBUG=false
APP_KEY=                          # Leave blank — generated in Step 8
APP_URL=https://your-domain.com   # Replace with your actual domain

# ─── MySQL ────────────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=                          # Internal MySQL hostname from Coolify
DB_PORT=3306
DB_DATABASE=booklix
DB_USERNAME=booklix_user
DB_PASSWORD=                      # Your MySQL password

# ─── Redis ────────────────────────────────────────────────────────
REDIS_HOST=                       # Internal Redis hostname from Coolify
REDIS_PASSWORD=                   # Your Redis password
REDIS_PORT=6379

# ─── Cache / Session / Queue ──────────────────────────────────────
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_LIFETIME=120

# ─── Mail (configure later via Admin → Settings) ──────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"

# ─── Filesystem ───────────────────────────────────────────────────
FILESYSTEM_DISK=local
```

> ⚠️ **Never** set `APP_DEBUG=true` in production. Error details would be exposed publicly.

---

## 5. Connect MySQL

The connection is handled via environment variables set in Step 4.
To verify the connection is working after deploy, run in the **Coolify terminal**:

```bash
php artisan db:show
```

Expected output shows your database name, host, and list of tables (after migration).

**Getting the internal MySQL host in Coolify:**

1. Open your MySQL service in Coolify
2. Go to the **Connection** tab
3. Look for **Internal** connection details — use the host shown there
4. The format is usually: `mysql-SERVICE_NAME` or an internal IP like `172.x.x.x`

---

## 6. Connect Redis

Same approach — use the internal hostname shown in the Redis service's **Connection** tab.

To verify Redis is connected after deploy:

```bash
php artisan tinker
# Then type:
Cache::put('test', 'hello', 60);
Cache::get('test');
# Should return: "hello"
```

---

## 7. Build & Deploy

### 7.1 — Nixpacks Configuration (Recommended)

Create a `nixpacks.toml` file in the project root if it doesn't exist:

```toml
[phases.setup]
nixPkgs = ["php82", "php82Extensions.dom", "php82Extensions.curl", "php82Extensions.pdo", "php82Extensions.pdo_mysql", "php82Extensions.mbstring", "php82Extensions.tokenizer", "php82Extensions.xml", "php82Extensions.ctype", "php82Extensions.gd", "php82Extensions.zip", "php82Extensions.redis", "composer", "nodejs_20", "npm"]

[phases.build]
cmds = [
  "composer install --no-dev --optimize-autoloader --no-interaction",
  "npm ci",
  "npm run build"
]

[start]
cmd = "php artisan serve --host=0.0.0.0 --port=8000"
```

### 7.2 — Trigger Deployment

1. In Coolify, go to your application
2. Click **Deploy** (or push a commit to `main` if auto-deploy is enabled)
3. Watch the build logs in real time

---

## 8. Post-Deploy Commands

After the first successful deploy, run these commands **in order** using Coolify's **Terminal** tab (or via SSH).

### Step 1 — Generate Application Key

```bash
php artisan key:generate
```

> This sets `APP_KEY` in your environment. **Do this only once.** Coolify will save the key automatically.

### Step 2 — Clear All Caches

```bash
php artisan optimize:clear
```

### Step 3 — Run Migrations

```bash
php artisan migrate --force
```

> The `--force` flag is required in production to bypass the confirmation prompt.

### Step 4 — Seed the Database

```bash
php artisan db:seed --force
```

> This creates the default Super Admin user, roles, permissions, and default settings.

### Step 5 — Storage Link

```bash
php artisan storage:link
```

> Creates a symbolic link from `public/storage` → `storage/app/public`, required for file uploads, logos, and media.

### Step 6 — Optimize for Production

```bash
php artisan optimize
```

> Caches config, routes, views, and events for maximum performance.

---

## 9. Storage Link & File Uploads

After running `php artisan storage:link` (Step 8), verify it worked:

```bash
ls -la public/storage
# Should show: public/storage -> ../storage/app/public
```

**If uploads don't appear after deploy:**

The symlink does not persist across container restarts on some setups. Add the storage link command to your deployment script in Coolify:

1. Go to your application in Coolify
2. Open **Configuration → Post-Deploy Commands**
3. Add:
   ```bash
   php artisan storage:link --force
   ```

For persistent file storage across deploys, consider:
- Mounting a **Coolify Storage Volume** to `/var/www/html/storage/app`
- Or using an S3-compatible object store (Cloudflare R2, MinIO, etc.)

---

## 10. Run Migrations & Seeders

### Normal Migration (after each deploy)

```bash
php artisan migrate --force
```

### Fresh Start (⚠️ destroys all data)

```bash
php artisan migrate:fresh --seed --force
```

> Only use `migrate:fresh` on a **new/empty** database. Never run it in production with real data.

### Rollback Last Migration

```bash
php artisan migrate:rollback --force
```

### Run Specific Seeder

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=DefaultSettingsSeeder --force
```

### Check Migration Status

```bash
php artisan migrate:status
```

---

## 11. Queue Worker (Background Jobs)

Booklix uses queues for email notifications, WhatsApp alerts, and PDF generation.

### Set Up in Coolify

1. In your application, go to **Workers** or **Background Services**
2. Add a new worker with:
   ```bash
   php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
   ```
3. Set **Auto-restart:** enabled

### Monitor Queue

```bash
# See pending jobs
php artisan queue:monitor

# Clear failed jobs
php artisan queue:flush

# Retry failed jobs
php artisan queue:retry all
```

---

## 12. Scheduler (Cron)

Laravel's scheduler handles recurring tasks (daily reports, cleanup, etc.).

### Set Up in Coolify

1. Go to your application → **Cron Jobs**
2. Add a new cron job:
   - **Schedule:** `* * * * *` (every minute)
   - **Command:** `php artisan schedule:run >> /dev/null 2>&1`

---

## 13. First Login

After migrations and seeding are complete:

1. Open your domain (e.g., `https://your-domain.com`)
2. You will see the **Booklix sign-in page**
3. Log in with the default Super Admin credentials:

| Field | Value |
|---|---|
| Email | `admin@booklix.com` |
| Password | `password` |

> ⚠️ **Change this password immediately** after first login via **Admin → Users → Edit Profile**.

### Filament Panel URLs

| Role | URL |
|---|---|
| Admin / Super Admin / Manager / Accountant | `/admin` |
| Partner | `/partner` |
| Transport Company | `/transport` |
| Greeter | `/greeter` |
| Driver (mobile) | `/driver` |

---

## 14. Troubleshooting

### 🔴 500 Server Error on first load

```bash
# Check logs
php artisan log:tail
# OR view the file directly
tail -f storage/logs/laravel.log
```

Most common causes:
- `APP_KEY` is empty → run `php artisan key:generate`
- Database not connected → verify `DB_HOST` is the **internal** hostname
- Migrations not run → run `php artisan migrate --force`
- Storage not linked → run `php artisan storage:link`

### 🔴 419 Page Expired (CSRF error)

```bash
# Verify SESSION_DRIVER is set correctly
php artisan config:show session

# Clear session cache
php artisan cache:clear
php artisan session:table  # only if using database sessions
```

### 🔴 Redis connection refused

```bash
# Test Redis connection
php artisan tinker
Redis::ping()  # Should return: "+PONG"
```

- Verify `REDIS_HOST` is the **internal** hostname (not the public IP)
- Verify `REDIS_PASSWORD` matches what you set in Coolify

### 🔴 Files/images not showing

```bash
# Re-run storage link
php artisan storage:link --force

# Verify permissions
chmod -R 775 storage bootstrap/cache
```

### 🔴 Filament assets not loading (404 on /admin)

```bash
php artisan filament:assets
php artisan optimize:clear
php artisan optimize
```

### 🔴 Queue jobs not processing

```bash
# Check if worker is running
php artisan queue:monitor

# Restart worker (in Coolify, restart the worker service)
php artisan queue:restart
```

---

## 🔄 Deployment Checklist

Use this checklist for every production deployment:

- [ ] Push code to `main` branch
- [ ] Coolify auto-deploys (or click Deploy manually)
- [ ] Run `php artisan migrate --force`
- [ ] Run `php artisan optimize`
- [ ] Verify `php artisan storage:link` is in post-deploy commands
- [ ] Check `storage/logs/laravel.log` for errors
- [ ] Test login at `/`
- [ ] Test admin panel at `/admin`

---

## 📦 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Admin Panel | Filament 4 |
| Database | MySQL 8+ |
| Cache / Session / Queue | Redis |
| Settings | Spatie Laravel Settings |
| Roles & Permissions | Spatie Laravel Permission |
| Media | Spatie Media Library |
| Activity Log | Spatie Activity Log |
| PDF Generation | Laravel DomPDF |
| Deployment | Coolify (self-hosted) |

---

## 📄 License

Proprietary — All rights reserved. © Booklix 2026.
