# 🐳 Docker Deployment Setup Guide

Deze guide helpt je om NEXA te deployen met Docker containers en Nginx als reverse proxy.

## 📋 Architectuur Overzicht

```
                            ┌─────────────────────┐
                            │   Internet/User     │
                            └──────────┬──────────┘
                                       │
                                       │ HTTP/HTTPS
                                       ▼
                            ┌─────────────────────┐
                            │   Nginx (Host)      │
                            │  Port 80/443        │
                            └──────────┬──────────┘
                                       │
                    ┌──────────────────┼──────────────────┐
                    │                  │                  │
                    ▼                  ▼                  ▼
         ┌─────────────────┐  ┌─────────────────┐  ┌──────────────┐
         │ Laravel Backend │  │   PostgreSQL    │  │     N8N      │
         │  Docker (9000)  │  │  Docker (5432)  │  │Docker (5678) │
         └─────────────────┘  └─────────────────┘  └──────────────┘
                PHP-FPM              Database          Automation
```

---

## 🎯 Stap 1: Installeer Vereisten op Server

SSH naar je server:
```bash
ssh user@192.168.178.116
```

### Installeer Docker & Docker Compose

```bash
# Update systeem
sudo apt update && sudo apt upgrade -y

# Installeer Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Voeg huidige user toe aan docker groep
sudo usermod -aG docker $USER
sudo usermod -aG docker github-runner

# Start Docker service
sudo systemctl enable docker
sudo systemctl start docker

# Installeer Docker Compose (v2)
sudo apt install docker-compose-plugin -y

# Verificeer installatie
docker --version
docker compose version
```

**BELANGRIJK**: Log uit en weer in zodat group membership actief wordt!

```bash
exit
ssh user@192.168.178.116
```

Test of Docker werkt zonder sudo:
```bash
docker ps
```

---

## 🎯 Stap 2: Installeer en Configureer Nginx

### Installeer Nginx

```bash
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

### Kopieer Nginx Configuratie

```bash
# Kopieer de configuratie van het project
cd /var/www/nexa
sudo cp deploy/nginx-nexa.conf /etc/nginx/sites-available/nexa

# Verwijder default site (optioneel)
sudo rm /etc/nginx/sites-enabled/default

# Enable nexa site
sudo ln -s /etc/nginx/sites-available/nexa /etc/nginx/sites-enabled/nexa

# Test configuratie
sudo nginx -t

# Als test OK is, reload Nginx
sudo systemctl reload nginx
```

---

## 🎯 Stap 3: Configureer Environment Variables

### Maak .env file voor productie

```bash
cd /var/www/nexa/backend

# Kopieer example naar .env
cp .env.example .env

# Edit .env met je productie settings
nano .env
```

### Belangrijke .env instellingen:

```env
APP_NAME="NEXA Skillmatching"
APP_ENV=production
APP_KEY=  # Wordt automatisch gegenereerd
APP_DEBUG=false
APP_URL=http://tosun.nl  # Of je domain

# Database (verbindt met Docker PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=db  # Docker container naam
DB_PORT=5432
DB_DATABASE=nexa
DB_USERNAME=nexa
DB_PASSWORD=STRONG_PASSWORD_HERE  # ⚠️ Wijzig dit!
```

**⚠️ BELANGRIJK**: Wijzig de database password naar een sterke password!

### Maak .env file in root voor docker-compose

```bash
cd /var/www/nexa

# Maak .env voor docker-compose
nano .env
```

Voeg toe:
```env
# Database credentials
DB_USERNAME=nexa
DB_PASSWORD=STRONG_PASSWORD_HERE  # Zelfde als in backend/.env
DB_DATABASE=nexa

# Application
APP_URL=http://tosun.nl

# N8N
N8N_HOST=n8n.tosun.nl
```

---

## 🎯 Stap 4: Eerste Deployment (Handmatig)

Voordat de automated deployment werkt, doen we een handmatige eerste setup:

```bash
cd /var/www/nexa

# Maak benodigde directories
mkdir -p backend/bootstrap/cache
mkdir -p backend/storage/framework/{cache,sessions,views}
mkdir -p backend/storage/logs
mkdir -p backend/storage/app/public

# Build assets
cd backend
npm ci
npm run build
cd ..

# Build en start Docker containers
docker compose -f docker-compose.deploy.yml build
docker compose -f docker-compose.deploy.yml up -d

# Wacht tot containers draaien
sleep 10

# Check of containers draaien
docker compose -f docker-compose.deploy.yml ps
```

### Run Laravel Setup Commands

```bash
cd /var/www/nexa

# Generate app key
docker compose -f docker-compose.deploy.yml exec backend php artisan key:generate --force

# Run migrations
docker compose -f docker-compose.deploy.yml exec backend php artisan migrate --force

# Cache configuratie
docker compose -f docker-compose.deploy.yml exec backend php artisan config:cache
docker compose -f docker-compose.deploy.yml exec backend php artisan route:cache
docker compose -f docker-compose.deploy.yml exec backend php artisan view:cache

# Create storage symlink
docker compose -f docker-compose.deploy.yml exec backend php artisan storage:link
```

### Set Permissions

```bash
sudo chown -R $USER:www-data /var/www/nexa/backend/storage
sudo chown -R $USER:www-data /var/www/nexa/backend/bootstrap/cache
sudo chmod -R 775 /var/www/nexa/backend/storage
sudo chmod -R 775 /var/www/nexa/backend/bootstrap/cache
```

---

## 🎯 Stap 5: Verificatie

### Check Docker Containers

```bash
docker compose -f /var/www/nexa/docker-compose.deploy.yml ps

# Output moet zijn:
# NAME            STATUS      PORTS
# nexa_backend    Up          127.0.0.1:9000->9000/tcp
# nexa_db         Up          127.0.0.1:5432->5432/tcp
# nexa_n8n        Up          127.0.0.1:5678->5678/tcp
```

### Check Nginx

```bash
sudo nginx -t
sudo systemctl status nginx
```

### Check Website

```bash
# Lokaal op server
curl -I http://localhost

# Vanuit browser
# Open: http://192.168.178.116
# Of: http://tosun.nl (als DNS is geconfigureerd)
```

### Check Logs

```bash
# Docker logs
docker compose -f /var/www/nexa/docker-compose.deploy.yml logs backend
docker compose -f /var/www/nexa/docker-compose.deploy.yml logs db
docker compose -f /var/www/nexa/docker-compose.deploy.yml logs n8n

# Laravel logs
tail -f /var/www/nexa/backend/storage/logs/laravel.log

# Nginx logs
sudo tail -f /var/log/nginx/nexa-error.log
sudo tail -f /var/log/nginx/nexa-access.log
```

---

## 🎯 Stap 6: Update GitHub Runner Permissions

De runner heeft extra rechten nodig voor Docker:

```bash
# Voeg runner user toe aan docker groep (als nog niet gedaan)
sudo usermod -aG docker github-runner

# Update sudoers voor docker-compose
sudo visudo -f /etc/sudoers.d/github-runner
```

Voeg deze regel toe (naast bestaande regels):
```
github-runner ALL=(ALL) NOPASSWD: /usr/bin/docker-compose
github-runner ALL=(ALL) NOPASSWD: /usr/bin/docker
```

Test als github-runner user:
```bash
sudo su - github-runner
docker ps
docker compose version
exit
```

---

## 🎯 Stap 7: Test Automated Deployment

Nu alles is setup, test de automated deployment:

```bash
# Trigger deployment via GitHub Actions
# Optie 1: Push naar main
git push origin main

# Optie 2: Manual trigger
# GitHub → Actions → Deploy to Production → Run workflow
```

Monitor de deployment:
```bash
# Runner logs
sudo journalctl -u actions.runner.*.service -f

# Docker logs
docker compose -f /var/www/nexa/docker-compose.deploy.yml logs -f

# Laravel logs
tail -f /var/www/nexa/backend/storage/logs/laravel.log
```

---

## 🔧 Maintenance Commands

### Stop Containers
```bash
cd /var/www/nexa
docker compose -f docker-compose.deploy.yml stop
```

### Start Containers
```bash
cd /var/www/nexa
docker compose -f docker-compose.deploy.yml start
```

### Restart Containers
```bash
cd /var/www/nexa
docker compose -f docker-compose.deploy.yml restart
```

### Rebuild Container (na code changes)
```bash
cd /var/www/nexa
docker compose -f docker-compose.deploy.yml build backend
docker compose -f docker-compose.deploy.yml up -d backend
```

### View Logs
```bash
# All containers
docker compose -f /var/www/nexa/docker-compose.deploy.yml logs -f

# Specific container
docker compose -f /var/www/nexa/docker-compose.deploy.yml logs -f backend
```

### Enter Container Shell
```bash
# Backend container
docker compose -f /var/www/nexa/docker-compose.deploy.yml exec backend bash

# Database container
docker compose -f /var/www/nexa/docker-compose.deploy.yml exec db psql -U nexa -d nexa
```

### Clean Up Old Images
```bash
# Remove unused images
docker image prune -a

# Remove unused volumes
docker volume prune
```

---

## 🔒 Security Checklist

### ✅ Moet je doen:

- [ ] **Wijzig database passwords** in `.env` files
- [ ] **Gebruik sterke APP_KEY** (wordt automatisch gegenereerd)
- [ ] **Configureer firewall** (ufw of iptables)
  ```bash
  sudo ufw allow 22/tcp    # SSH
  sudo ufw allow 80/tcp    # HTTP
  sudo ufw allow 443/tcp   # HTTPS
  sudo ufw enable
  ```
- [ ] **Setup SSL certificaat** met Let's Encrypt
  ```bash
  sudo apt install certbot python3-certbot-nginx
  sudo certbot --nginx -d tosun.nl -d www.tosun.nl
  ```
- [ ] **Reguliere backups** van database en storage
- [ ] **Monitor disk space** (Docker kan veel ruimte innemen)

### ⚠️ Niet doen:

- ❌ Expose Docker ports naar internet (alleen 127.0.0.1)
- ❌ Run containers als root (Dockerfile gebruikt www-data)
- ❌ Commit `.env` files naar git
- ❌ Debug mode aan laten in productie

---

## 🐛 Troubleshooting

### Container start niet

```bash
# Check logs
docker compose -f /var/www/nexa/docker-compose.deploy.yml logs backend

# Check of port al in gebruik is
sudo netstat -tulpn | grep 9000
```

### Database connection failed

```bash
# Check of database container draait
docker compose -f /var/www/nexa/docker-compose.deploy.yml ps

# Test database connectie
docker compose -f /var/www/nexa/docker-compose.deploy.yml exec backend \
  php artisan tinker
>>> \DB::connection()->getPdo();
```

### Nginx 502 Bad Gateway

```bash
# Check of PHP-FPM container draait
docker ps | grep nexa_backend

# Check Nginx kan connecten naar container
curl -v http://127.0.0.1:9000

# Check logs
sudo tail -f /var/log/nginx/nexa-error.log
```

### Permission errors

```bash
# Fix ownership
sudo chown -R www-data:www-data /var/www/nexa/backend/storage
sudo chmod -R 775 /var/www/nexa/backend/storage
```

### Container heeft geen internet

```bash
# Check Docker network
docker network inspect nexa_nexa_network

# Restart Docker service
sudo systemctl restart docker
```

---

## 📊 Monitoring

### Check Resource Usage

```bash
# Container stats
docker stats

# Disk usage
docker system df
df -h /var/www

# Memory
free -h
```

### Setup Monitoring (Optioneel)

```bash
# Install htop
sudo apt install htop

# Monitor containers
watch -n 1 'docker compose -f /var/www/nexa/docker-compose.deploy.yml ps'
```

---

## 🔄 Deployment Flow

Na deze setup is de deployment flow:

```
1. Developer pusht naar main branch
2. GitHub Actions workflow wordt getriggerd
3. Runner op server voert deployment uit:
   - Git pull latest code
   - Build assets (npm)
   - Build Docker images
   - Start/restart containers
   - Run Laravel commands in container
   - Reload Nginx
4. Site is live met nieuwe code!
```

---

## 📚 Nuttige Resources

- Docker Docs: https://docs.docker.com/
- Docker Compose Docs: https://docs.docker.com/compose/
- Nginx Docs: https://nginx.org/en/docs/
- Laravel Deployment: https://laravel.com/docs/deployment
- PostgreSQL Docker: https://hub.docker.com/_/postgres

---

## ✅ Quick Checklist

Gebruik dit om te verifiëren dat alles werkt:

- [ ] Docker geïnstalleerd en draait
- [ ] Docker Compose geïnstalleerd
- [ ] Nginx geïnstalleerd en configured
- [ ] Nginx config gekopieerd en enabled
- [ ] .env files aangemaakt en geconfigureerd
- [ ] Database passwords gewijzigd
- [ ] Containers draaien (docker compose ps)
- [ ] Website bereikbaar (curl http://localhost)
- [ ] GitHub runner heeft docker rechten
- [ ] Automated deployment getest
- [ ] Logs zijn schoon (geen errors)
- [ ] Firewall geconfigureerd
- [ ] SSL certificaat installed (optioneel)

---

**Gefeliciteerd! Je NEXA platform draait nu in Docker containers! 🎉**

Voor vragen of problemen, zie de troubleshooting sectie of check de logs.





