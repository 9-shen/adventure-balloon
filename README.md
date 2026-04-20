# 🎈 Booklix — CRM + Booking + Dispatch Platform

> **Stack:** Laravel 12 · Filament 4 · MySQL 8 · Redis · PHP 8.2+
> **Deployment:** Coolify (self-hosted PaaS) · Docker (nginx + php-fpm + supervisor)

---

## 📋 Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Add SSH Key to GitHub](#2-add-ssh-key-to-github)
3. [Create Services in Coolify](#3-create-services-in-coolify)
4. [Create the Application](#4-create-the-application)
5. [Configure Network & Domain](#5-configure-network--domain)
6. [Generate APP_KEY](#6-generate-app_key)
7. [Environment Variables](#7-environment-variables)
8. [Force HTTPS (AppServiceProvider)](#8-force-https-appserviceprovider)
9. [Configure Persistent Storage](#9-configure-persistent-storage)
10. [Verify Docker Files](#10-verify-docker-files)
11. [Deploy](#11-deploy)
12. [Verify Deployment](#12-verify-deployment)
13. [Post-Deploy Commands (Manual)](#13-post-deploy-commands-manual)
14. [Migrations & Seeders Reference](#14-migrations--seeders-reference)
15. [First Login](#15-first-login)
16. [What start.sh Does on Every Deploy](#16-what-startsh-does-on-every-deploy)
17. [Re-deploying After Changes](#17-re-deploying-after-changes)
18. [Troubleshooting](#18-troubleshooting)
19. [Tech Stack](#19-tech-stack)

---

## 1. Prerequisites

Before starting, make sure you have:

- ✅ Coolify v4+ installed and running on your VPS
- ✅ MySQL 8 service running in Coolify
- ✅ Redis service running in Coolify
- ✅ Your Booklix repo on GitHub
- ✅ These files exist in your repo (they do — created already):
  ```
  Dockerfile
  docker/start.sh
  docker/nginx.conf
  docker/php.ini
  docker/supervisord.conf
  .dockerignore
  ```

> ⚠️ **Critical:** Make sure `docker` is **NOT** listed in `.dockerignore`. If it is, the container will fail to build with a "not found" error.

---

## 2. Add SSH Key to GitHub

Coolify uses SSH to pull your private repository. You must add Coolify's SSH key to GitHub.

1. In Coolify, go to **Keys & Tokens → SSH Keys**
2. Copy the public key shown
3. Go to **GitHub → Settings → SSH and GPG keys → New SSH key**
4. Paste the key and save

Verify your repo URL uses **SSH format** (not HTTPS):

```
git@github.com:9-shen/adventure-balloon.git   ✅
https://github.com/9-shen/adventure-balloon    ❌
```

---

## 3. Create Services in Coolify

You need **two database services** before creating the app.

### 3.1 — Create MySQL 8 Database

1. Coolify → **Resources → New Resource → Database → MySQL**
2. Choose version **8.0**
3. Set:
   - **Database Name:** `booklix`
   - **Username:** `root` *(safest for Coolify — avoids permission issues)*
   - **Password:** *(generate a strong password and save it)*
4. Click **Create** — wait for it to start (green dot)
5. Click the service → go to **Connection** tab
6. Copy the **Internal URL** — it looks like:
   ```
   mysql://root:PASSWORD@t8s04wcwwg448ck00w0wkccs:3306/booklix
   ```
   Extract:
   - `DB_HOST` = `t8s04wcwwg448ck00w0wkccs` (the long internal hostname)
   - `DB_PORT` = `3306`
   - `DB_DATABASE` = `booklix`
   - `DB_USERNAME` = `root`
   - `DB_PASSWORD` = `PASSWORD`

> ⚠️ Always use the **internal hostname** (the long string), NOT an IP address. IPs can change on container restart.

> ⚠️ If you use a custom username (not root), you may get `Access denied` errors. Either use `root` or manually grant permissions in phpMyAdmin.

### 3.2 — Create Redis

1. Coolify → **Resources → New Resource → Database → Redis**
2. Set a **password** (save it — use `null` if you want no password)
3. Click **Create** — wait for it to start
4. Click the service → **Connection** tab → copy the **Internal URL**:
   ```
   redis://:PASSWORD@HOSTNAME:6379
   ```
   Extract:
   - `REDIS_HOST` = `HOSTNAME`
   - `REDIS_PORT` = `6379`
   - `REDIS_PASSWORD` = `PASSWORD`

---

## 4. Create the Application

1. Coolify → **Resources → New Resource → Application**
2. Select **Private Repository (GitHub App or SSH)**
3. Paste repo URL: `git@github.com:9-shen/adventure-balloon.git`
4. Branch: `main`
5. **Build Pack: `Dockerfile`** ← **MUST be Dockerfile, NOT Nixpacks**
6. Dockerfile location: `/Dockerfile`
7. Click **Continue**

> ⚠️ Do **not** use Nixpacks. The `Dockerfile` approach gives you full control over nginx, php-fpm, supervisor, and the startup script.

---

## 5. Configure Network & Domain

1. Go to **Configuration → General → Network**
2. Set **Ports Exposes:** `80` ← must match `docker/nginx.conf` which listens on port 80
3. Leave **Ports Mappings** empty
4. Click **Save**

Then set your domain:

1. **Configuration → General → Domains**
2. Enter: `https://your-domain.com`
3. Click **Save**

---

## 6. Generate APP_KEY

Run this on your **local machine** (not in Coolify):

```bash
php artisan key:generate --show
```

Copy the output — it looks like:

```
base64:AbCdEfGhIjKlMnOpQrStUvWxYz1234567890==
```

You will paste this as `APP_KEY` in Step 7.

> Do this **once only**. Changing the key in production will invalidate all existing sessions, encrypted data, and remember-me tokens.

---

## 7. Environment Variables

In Coolify → your application → **Configuration → Environment Variables**

> 💡 Use the **"Paste as .env"** button to paste all variables at once.

```env
# ─── Application ─────────────────────────────────────────────────────
APP_NAME=Booklix
APP_ENV=production
APP_KEY=base64:your_generated_key_here
APP_DEBUG=false
APP_URL=https://your-domain.com
ASSET_URL=https://your-domain.com
APP_LOCALE=en
APP_FALLBACK_LOCALE=en

# ─── Database (MySQL) ─────────────────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=your_mysql_internal_hostname
DB_PORT=3306
DB_DATABASE=booklix
DB_USERNAME=root
DB_PASSWORD=your_mysql_password

# ─── Redis ────────────────────────────────────────────────────────────
REDIS_CLIENT=phpredis
REDIS_HOST=your_redis_internal_hostname
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password

# ─── Cache / Session / Queue ──────────────────────────────────────────
CACHE_STORE=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120
QUEUE_CONNECTION=redis

# ─── Filesystem ───────────────────────────────────────────────────────
FILESYSTEM_DISK=local

# ─── Logging ──────────────────────────────────────────────────────────
LOG_CHANNEL=stack
LOG_LEVEL=error

# ─── Mail (configure when ready) ──────────────────────────────────────
MAIL_MAILER=log
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

> ⚠️ **Never** set `APP_DEBUG=true` in production — it exposes error stack traces publicly.

> ⚠️ **Always** set both `APP_URL` and `ASSET_URL` to `https://`. Without `ASSET_URL`, Filament's CSS and JavaScript will load over HTTP, causing **mixed content errors** and a completely broken admin panel UI.

---

## 8. Force HTTPS (AppServiceProvider)

This is already done in `app/Providers/AppServiceProvider.php`. It forces HTTPS when behind Coolify's reverse proxy:

```php
public function boot(): void
{
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

> ✅ This only activates when `APP_ENV=production`. Your local development on `http://localhost` is completely unaffected.

---

## 9. Configure Persistent Storage

Uploaded files (booking media, logos, documents) must survive container rebuilds.

1. Coolify → your application → **Configuration → Persistent Storage**
2. Click **Add Volume**
3. Set:
   - **Destination Path:** `/var/www/html/storage`
   - **Source Path:** *(leave empty — Coolify auto-assigns)*
4. Click **Save**

> ⚠️ Without a persistent volume, **all uploaded files are deleted** every time you redeploy.

---

## 10. Verify Docker Files

Make sure all 5 Docker files exist in your GitHub repo:

```
Dockerfile                  ← Multi-stage build (PHP 8.2 + nginx + supervisor)
docker/start.sh             ← Runs on every deploy (migrations, cache, etc.)
docker/nginx.conf           ← Nginx config (listens on port 80)
docker/php.ini              ← PHP settings (upload limits, OPcache)
docker/supervisord.conf     ← Process manager (nginx + php-fpm + queue + scheduler)
.dockerignore               ← Excludes node_modules, vendor, .env etc.
```

> ⚠️ **Critical:** Open `.dockerignore` and confirm `docker` or `docker/` is **NOT** listed. If it is, the build will fail with `/docker/start.sh: not found`.

---

## 11. Deploy

1. Coolify → your application → click **Deploy**
2. Watch the **Deployment Logs** in real time
3. Build takes approximately **3–5 minutes**
4. You should see: `Rolling update completed.`

For subsequent deploys, just push to GitHub:

```bash
git add .
git commit -m "your change"
git push origin main
# Then click Deploy in Coolify (or enable auto-deploy)
```

---

## 12. Verify Deployment

Once deployed, test these URLs:

| URL | Expected Result |
|---|---|
| `https://your-domain.com` | Booklix login page |
| `https://your-domain.com/admin` | Filament admin login |
| `https://your-domain.com/partner` | Partner portal login |
| `https://your-domain.com/driver` | Driver portal (mobile) |

---

## 13. Post-Deploy Commands (Manual)

> 💡 These are handled **automatically** by `docker/start.sh` on every deploy.
> Only run these manually if something went wrong.

Open the **Terminal** tab in Coolify (or SSH into the container):

```bash
# 1. Generate app key (first time only)
php artisan key:generate

# 2. Run migrations
php artisan migrate --force

# 3. Seed the database (first time only)
php artisan db:seed --force

# 4. Link storage
php artisan storage:link

# 5. Publish Filament assets (CSS/JS for admin panel)
php artisan filament:assets

# 6. Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Check logs
cat /var/www/html/storage/logs/laravel.log
```

---

## 14. Migrations & Seeders Reference

### Run migrations (normal — after each deploy)
```bash
php artisan migrate --force
```

### Fresh start ⚠️ (destroys ALL data — only on empty DB)
```bash
php artisan migrate:fresh --seed --force
```

### Rollback last batch
```bash
php artisan migrate:rollback --force
```

### Check migration status
```bash
php artisan migrate:status
```

### Run specific seeders
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder --force
php artisan db:seed --class=DefaultSettingsSeeder --force
```

---

## 15. First Login

After migrations and seeding complete:

1. Open `https://your-domain.com`
2. You will see the **Booklix sign-in page**
3. Log in with the default Super Admin:

| Field | Value |
|---|---|
| Email | `admin@booklix.com` |
| Password | `password` |

> ⚠️ **Change this password immediately** after first login.

### Filament Panel URLs

| Role | Panel URL |
|---|---|
| Admin / Super Admin / Manager / Accountant | `/admin` |
| Partner | `/partner` |
| Transport Company | `/transport` |
| Greeter | `/greeter` |
| Driver (mobile-optimized) | `/driver` |

---

## 16. What start.sh Does on Every Deploy

`docker/start.sh` runs automatically when the container starts. It executes:

```
1. php artisan migrate --force         → applies any new database migrations
2. php artisan db:seed --force         → seeds roles, permissions, default settings
3. php artisan storage:link            → links storage to public/storage
4. php artisan filament:assets         → publishes Filament CSS/JS (critical for admin panel)
5. php artisan config:cache            → caches config for performance
6. php artisan route:cache             → caches routes
7. php artisan view:cache              → caches Blade views
8. supervisord starts 4 processes:
   ├── nginx                           → web server (port 80)
   ├── php-fpm                         → PHP processor
   ├── queue:work redis                → processes emails, PDFs, WhatsApp alerts
   └── scheduler loop                  → runs php artisan schedule:run every 60s
```

---

## 17. Re-deploying After Changes

```bash
# 1. Make changes locally
git add .
git commit -m "describe your change"
git push origin main

# 2. Go to Coolify → click Deploy
# OR enable auto-deploy in Coolify settings (deploys on every push)
```

No settings need to change between deploys — `start.sh` handles everything automatically.

---

## 18. Troubleshooting

| Error | Likely Cause | Fix |
|---|---|---|
| `Permission denied (publickey)` | SSH key not added to GitHub | Add Coolify's SSH key to GitHub Settings |
| `/docker/start.sh not found` | `docker/` in `.dockerignore` | Remove `docker` line from `.dockerignore` |
| `Access denied for user` | Wrong DB credentials | Use `root` user or grant privileges in phpMyAdmin |
| `500 Server Error` | Laravel app error | Check `storage/logs/laravel.log` |
| `419 Page Expired` | Session/CSRF issue | Verify `SESSION_DRIVER=redis` and Redis is connected |
| `Filament CSS not loading` | Mixed content (http vs https) | Set `ASSET_URL=https://...` + add `URL::forceScheme` |
| `Spinner only / blank admin` | Filament assets not published | Add `php artisan filament:assets` to `start.sh` |
| `Redis connection refused` | Wrong `REDIS_HOST` | Use the **internal** hostname, not a public IP |
| `502 Bad Gateway` | nginx config syntax error | Check `docker/nginx.conf` — count `{` and `}` |
| Files not showing after redeploy | No persistent volume | Add persistent storage volume at `/var/www/html/storage` |
| Container keeps crashing | Error in `start.sh` | Remove `set -e` — errors should not stop boot |

### View Laravel Logs

```bash
# In Coolify → Terminal tab
cat /var/www/html/storage/logs/laravel.log

# Or tail live
tail -f /var/www/html/storage/logs/laravel.log
```

### Test Redis Connection

```bash
php artisan tinker
Redis::ping()  # Should return: "+PONG"
```

### Test DB Connection

```bash
php artisan db:show
```

---

## 🔄 Deployment Checklist

Use this for every production deployment:

- [ ] All Docker files exist in repo (`Dockerfile`, `docker/start.sh`, `docker/nginx.conf`, `docker/php.ini`, `docker/supervisord.conf`)
- [ ] `docker` is **NOT** in `.dockerignore`
- [ ] `APP_URL` and `ASSET_URL` both set to `https://`
- [ ] `APP_KEY` is set (not blank)
- [ ] Persistent storage volume configured at `/var/www/html/storage`
- [ ] Push to `main` and click **Deploy** in Coolify
- [ ] Deployment log ends with `Rolling update completed.`
- [ ] Test login at `https://your-domain.com`
- [ ] Test admin panel at `https://your-domain.com/admin`
- [ ] Check `storage/logs/laravel.log` for errors

---

## 19. Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 |
| Admin Panel | Filament 4 |
| Database | MySQL 8+ |
| Cache / Session / Queue | Redis |
| Web Server | Nginx |
| PHP Processor | PHP-FPM 8.2 |
| Process Manager | Supervisord |
| Settings | Spatie Laravel Settings |
| Roles & Permissions | Spatie Laravel Permission |
| Media | Spatie Media Library |
| Activity Log | Spatie Activity Log |
| PDF Generation | Laravel DomPDF |
| Containerization | Docker (multi-stage build) |
| Deployment | Coolify (self-hosted PaaS) |

---

## 📄 License

Proprietary — All rights reserved. © Booklix 2026.
