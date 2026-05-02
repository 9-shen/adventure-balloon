# 🚀 Adventure Balloon (Booklix) — Installation & Deployment Guide

> Step-by-step instructions for both **local development** and **production deployment** on Contabo VPS with Coolify.

---

## 📑 Table of Contents

- [Local Development Setup](#-local-development-setup)
- [Production Deployment — Contabo VPS + Coolify](#-production-deployment--contabo-vps--coolify)
- [Docker Architecture](#-docker-architecture)
- [Troubleshooting](#-troubleshooting)
- [Tech Stack Reference](#-tech-stack-reference)

---

# 💻 Local Development Setup

## 📋 Prerequisites

Make sure the following are installed on your machine **before** you begin:

| Requirement | Version | Download |
|---|---|---|
| **PHP** | `^8.2` | https://www.php.net/downloads |
| **Composer** | Latest | https://getcomposer.org/download |
| **Node.js & npm** | LTS (v20+) | https://nodejs.org |
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
npm install --legacy-peer-deps
```

> ⚠️ Use `--legacy-peer-deps` — Filament 4 has peer dependency conflicts with some npm packages that require this flag.

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

This section covers deploying the application on a **Contabo VPS** managed by **Coolify**, using a 3-stage Docker build.

## 📋 Prerequisites

Before you start, make sure you have:

| Requirement | Details |
|---|---|
| **Contabo VPS** | Ubuntu 24.04 LTS (recommended), minimum 4 vCPU / 8 GB RAM |
| **Coolify** | v4.x installed on the VPS |
| **Domain** | DNS A record pointing to VPS IP |
| **GitHub repo** | `9-shen/adventure-balloon` (private or public) |
| **GitHub Token** | Personal Access Token with `repo` scope |

> ⚠️ **VPS Resource Warning:** The Docker build compiles several PHP extensions from source (`gd`, `intl`, `mbstring`, `redis`). On a low-resource VPS this can take 10–20 minutes. If your VPS CPU load exceeds 500% during the build, the build will timeout. See [Build Timeout](#build-times-out) in the troubleshooting section.

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

> **Note:** All environment variables set in Coolify are injected at **runtime** into the container. The `APP_KEY` is the only one needed at build time — a temporary dummy key is baked into the image during `docker build`, and the real key is injected by Coolify at container start via `start.sh`.

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
2. Monitor the **Build Logs** — the full build takes approximately **15–25 minutes** on first deploy:
   - Stage 0: Composer dependencies (`composer:2` image, ~60s)
   - Stage 1: Vite/npm build (`node:20-alpine`, ~3 min) — includes copying vendor for Filament CSS
   - Stage 2: PHP runtime + extension compilation (`php:8.2-fpm-alpine`, **10–20 min** — see note below)
3. Once deployed, the **container startup** runs `start.sh` which:
   - Creates/fixes storage directory permissions (`chmod 777`)
   - Clears stale caches
   - Runs `php artisan package:discover` (non-fatal)
   - Runs `php artisan migrate --force` (non-fatal — warns if fails)
   - Runs `php artisan db:seed --force` (non-fatal)
   - Links public storage
   - Publishes Filament/Livewire assets
   - Caches config and views (skips route cache — see below)
   - Starts Nginx + PHP-FPM + Queue Worker + Scheduler via Supervisor

> ⚠️ **Why extension compilation takes time:** The `php:8.2-fpm-alpine` image compiles `pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache redis` from C source. This uses significant CPU. On subsequent deploys, Docker layer cache avoids re-running this if no Dockerfile changes were made.

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
- [ ] Configure Twilio/WhatsApp settings in **Admin → Settings → WhatsApp** (for driver notifications)
- [ ] Set up a custom domain and verify SSL is active (🔒 padlock in browser)
- [ ] Enable Coolify's **Auto-deploy on push** (webhook) for future deploys
- [ ] Add a Coolify **Volume** for `/var/www/html/storage/app/public` to persist uploads across redeploys
- [ ] (Optional) Set up automated backups — see `docs/phases/phase-29-minio-backup.md`

---

## 🔄 Redeployment (After Code Changes)

Every time you push to the `main` branch, Coolify can automatically redeploy. To enable this:

1. In your Coolify application, go to **Settings**
2. Enable **Automatic Deployment** (webhook trigger on push)
3. Coolify will rebuild the Docker image and restart the container

Manual redeploy is also available with one click from the Coolify dashboard.

> **Build cache:** If your `Dockerfile` itself hasn't changed, Docker's layer cache will skip the extension compilation step (~10–20 min saved). Only changes to `Dockerfile`, `composer.json`, or `package.json` invalidate those stages.

---

# 🐳 Docker Architecture

Understanding the Docker setup helps when debugging builds.

## 3-Stage Build

```
Stage 0 (deps)       Stage 1 (builder)        Stage 2 (production)
──────────────       ─────────────────        ────────────────────
composer:2           node:20-alpine           php:8.2-fpm-alpine

composer install     npm ci                   apk install libs
--ignore-platform    npm run build            docker-php-ext-install
                                              pecl install redis
COPY → vendor/  ──→  COPY vendor/ (Filament)
                     COPY → public/build/ ──→ COPY public/build/
                                              COPY vendor/ ←──────
```

### Why `--ignore-platform-reqs` in Stage 0?
The `composer:2` image has PHP 8.x but may not have the exact extensions your `composer.json` requires (`ext-intl`, `ext-gd`, etc.). The flag lets composer install packages without checking extension requirements. Extensions are compiled in Stage 2 where they actually matter.

### Why vendor is copied into Stage 1 (the Node stage)?
Filament 4 uses a CSS import like:
```css
@import '../../../../vendor/filament/filament/resources/css/theme.css';
```
Vite resolves this at build time. Without `vendor/` in the Node stage, the Vite build **fails** with a "can't resolve" error.

### Why `fastcgi_pass 127.0.0.1:9000` in nginx.conf?
Alpine's `php-fpm` listens on TCP port 9000 by default, **not** a Unix socket. Using `fastcgi_pass unix:/var/run/php-fpm.sock` will cause a 502 Bad Gateway on Alpine. Always use TCP for Alpine-based images.

### Why is `route:cache` skipped?
Filament and Livewire register routes dynamically inside service providers at runtime. Running `php artisan route:cache` bakes a static snapshot that **misses these dynamic routes**, causing "Unable to find component" errors and broken panel navigation.

### Why `set -e` is removed from start.sh?
With `set -e`, any non-zero exit code (even a warning from `migrate` or `package:discover`) would kill the startup script before Supervisor is launched — meaning no web server starts at all. Errors are logged as warnings but don't stop the container from starting.

### Supervisor processes

| Process | Command | Purpose |
|---|---|---|
| `php-fpm` | `php-fpm` | PHP request handler |
| `nginx` | `nginx -g "daemon off;"` | Web server |
| `queue-worker` | `php artisan queue:work --queue=notifications,default` | Processes WhatsApp/email jobs |
| `scheduler` | `while true; do php artisan schedule:run; sleep 60; done` | Runs Laravel scheduled tasks every minute |

---

# 🛠️ Troubleshooting

## Production Issues

### Build times out (>30 min or CPU overloaded)

**Symptom:** Coolify build log hangs at extension compilation. VPS CPU load reaches 400–800%.

**Root causes and fixes:**

| Approach | Pros | Cons |
|---|---|---|
| `php:8.2-fpm-alpine` (current) | Small image, free | Compiles from source — slow on first build |
| `serversideup/php:8.2-fpm-nginx` | Pre-built extensions, fast | Uses `apt-get` not `docker-php-ext-install` — different install method |
| Ubuntu-based image | Fast `apt` installs | Larger image (~500 MB vs ~200 MB) |

**If using Alpine and it times out:**
- Check Docker layer cache is working (only re-runs if Dockerfile changed)
- Temporarily upgrade VPS plan for the first build
- Consider switching to `serversideup/php:8.2-fpm-nginx` and adding missing extensions via `apt-get install libicu-dev libgd-dev && docker-php-ext-install intl gd`

> ⚠️ When using `serversideup`, do NOT use `apt-get install php8.2-intl` — it installs a conflicting system PHP. Use `docker-php-ext-install intl` instead after installing the dev libraries.

### Vite build fails: "can't resolve vendor/filament/.../theme.css"

**Cause:** The Node stage doesn't have the `vendor/` directory. Filament's CSS imports require it at build time.

**Fix:** Make sure the Dockerfile has:
```dockerfile
COPY --from=deps /app/vendor ./vendor
```
in Stage 1 (the node stage), **before** `RUN npm run build`.

### Container starts but app shows 502 Bad Gateway

**Cause:** Nginx cannot reach PHP-FPM. Common on Alpine images.

**Fix:** Ensure `nginx.conf` uses TCP, not a socket:
```nginx
fastcgi_pass 127.0.0.1:9000;   ✅ correct for Alpine
# fastcgi_pass unix:/run/php-fpm.sock;  ❌ wrong for Alpine
```

### Container starts but immediately crashes / no logs

**Cause:** `set -e` in `start.sh` killed the script before Supervisor launched (e.g., a `migrate` warning was treated as a fatal error).

**Fix:** `start.sh` must NOT have `set -e`. Use `|| true` or `|| echo "warning"` for non-fatal commands.

### "Unable to find component" / Livewire Errors

- Ensure you are NOT running `php artisan route:cache` in production
- Verify `start.sh` is skipping route cache (step 10)
- Run `php artisan package:discover --ansi` if components are newly added

### Migrations fail on first deploy

The `start.sh` script runs migrations automatically on every container start. If they fail:
1. Check `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` in Coolify env vars
2. Verify the MySQL service is running and healthy in Coolify
3. Check that `DB_HOST` exactly matches the internal hostname shown in the MySQL service settings

### Files / uploads not persisting between deploys

By default, `storage/app/public` lives inside the container and is wiped on each redeploy. To persist uploads:

1. In Coolify, add a **Volume** mount: `/var/www/html/storage/app/public` → a named volume
2. Or use an S3-compatible storage (MinIO) — see `docs/phases/phase-29-minio-backup.md`

### Storage permission errors (`chmod: Operation not permitted`)

**Fix in start.sh** — use `chmod 777` before `chown`, and run them separately:
```sh
chmod -R 777 /var/www/html/storage
chmod -R 777 /var/www/html/bootstrap/cache
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
```

---

## Local Development Issues

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

### npm install fails with peer dependency errors

```bash
npm install --legacy-peer-deps
```

Filament 4 requires this flag due to peer dependency conflicts in its npm packages.

### Migrations fail with `Table already exists`

Your database has stale tables. Either drop and recreate the database, or run:
```bash
php artisan migrate:fresh --seed
```
> ⚠️ This **wipes all data**. Only use on a fresh install.

### Media / avatar images not showing

Make sure you ran `php artisan storage:link`. Check that `storage/app/public` is symlinked to `public/storage`.

---

# 📦 Tech Stack Reference

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
| Web Server | Nginx + PHP-FPM (Alpine, via Supervisor) |
| Container | Docker 3-stage build (composer → node → php-alpine) |
| Hosting | Contabo VPS + Coolify |
| Notifications | Twilio (WhatsApp API) + WhatsApp Web (wa.me links) |

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
│   ├── nginx.conf          ← Nginx server block (TCP fastcgi 127.0.0.1:9000)
│   ├── php.ini             ← PHP runtime config
│   ├── supervisord.conf    ← Supervisor (nginx + php-fpm + queue + scheduler)
│   └── start.sh            ← Container bootstrap (no set -e, non-fatal errors)
├── docs/                   ← Project documentation & phase specs
└── storage/
    └── app/public/         ← Uploaded files (symlinked to public/storage)
```

---

*Last updated: May 2026 — Adventure Balloon v1.0 (Phase 28+)*
