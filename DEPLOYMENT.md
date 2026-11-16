# Deployment Runbook — Inventory Stock

Instructional guide to deploy this project on a VPS using Docker & Caddy reverse proxy.

## Overview
- **Stack:** Laravel 13 (PHP 8.3) with FrankenPHP
- **Database:** SQLite (persisted in volume `database_persistent/database.sqlite`)
- **Reverse Proxy:** Caddy (Automatic HTTPS / SSL via Let's Encrypt)
- **Target OS:** Ubuntu 22.04+ LTS / Debian / RHEL with Docker installed

## Values You Must Provide
| Environment Variable / Config | Description | Example |
|---|---|---|
| `DOMAIN` | Your domain name (pointed via A record to VPS IP) | `stock.example.com` |
| `APP_KEY` | Laravel encryption key | `base64:...` |
| `APP_URL` | Full HTTPS URL of your application | `https://stock.example.com` |

## Prerequisites
1. VPS with public IP address and root / sudo access.
2. A Record in your Domain DNS Manager pointing to your VPS IP address.
3. Docker & Docker Compose installed on VPS:
   ```bash
   command -v docker >/dev/null || curl -fsSL https://get.docker.com | sh
   docker compose version
   ```

## Step 1 — Clone Repository on VPS
Connect to your VPS using Termius or SSH and clone the codebase:
```bash
mkdir -p /opt/inventory_stock
git clone git@github.com:Jehan46635621/inventory_stock.git /opt/inventory_stock
cd /opt/inventory_stock
```

## Step 2 — Configure Production Environment
Create `.env` file on the VPS:
```bash
cat > /opt/inventory_stock/.env <<'EOF'
APP_NAME="Inventory Stock"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_APP_KEY
APP_DEBUG=false
APP_URL=https://YOUR_DOMAIN

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=sqlite
EOF

chmod 600 /opt/inventory_stock/.env
```

Set your domain for Caddy (optional, or replace `{$DOMAIN:localhost}` in `Caddyfile` directly with your domain name):
```bash
export DOMAIN=YOUR_DOMAIN
```

## Step 3 — Build and Start Services
```bash
cd /opt/inventory_stock
docker compose up -d --build
```

## Verification
1. Check containers status:
   ```bash
   docker compose ps
   ```
2. Check logs:
   ```bash
   docker compose logs -f
   ```
3. Visit `https://YOUR_DOMAIN` in your browser.
