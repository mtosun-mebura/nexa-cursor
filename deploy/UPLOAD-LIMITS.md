# Grote uploads (tenant-ZIP import)

Fout **413 Request Entity Too Large** komt bijna altijd van **nginx** (of een load balancer) vóór Laravel.

## 1. Nginx (nexasuite.nl / reverse proxy naar Docker)

In de `server`-block van je site (bijv. `/etc/nginx/sites-available/nexasuite`):

```nginx
client_max_body_size 512M;
```

Daarna:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

Referentie: `deploy/nginx-nexa.conf`.

## 2. PHP / Laravel (Docker backend)

- `backend/php-upload.ini` → gekopieerd in `Dockerfile.prod`
- `UploadServiceProvider` zet limieten uit `config/upload.php`
- Na deploy: `docker compose -f docker-compose.deploy.yml build --no-cache backend && docker compose -f docker-compose.deploy.yml up -d backend`

## 3. Optioneel in `.env`

```env
UPLOAD_MAX_FILESIZE=512M
POST_MAX_SIZE=520M
TENANT_BUNDLE_MAX_KB=512000
```
