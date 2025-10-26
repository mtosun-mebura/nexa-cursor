# 🚀 NEXA Deployment op Proxmox Ubuntu (192.168.178.116)

## Deployment Flow Overzicht

```
GitHub → Self-Hosted Runner → Deployment op 192.168.178.116
    ↓
1. 📥 Git pull in /var/www/nexa
2. 🔧 Build assets (npm run build)
3. 🐳 Build Docker containers
4. ⚙️  Setup Laravel (migrations, cache)
5. 🚀 Start Docker container op poort 8000
6. ✅ Verificatie
```

---

## 📋 Eerste Setup (Eenmalig)

### 1. SSH naar Proxmox Ubuntu Server

```bash
ssh user@192.168.178.116
```

### 2. Installeer Vereisten

```bash
# Update systeem
sudo apt update && sudo apt upgrade -y

# Installeer Docker
curl -fsSL https://get.docker.com | sudo sh
sudo usermod -aG docker $USER
sudo usermod -aG docker github-runner

# Installeer Docker Compose
sudo apt install docker-compose-plugin -y

# Installeer Nginx
sudo apt install nginx -y

# Installeer Node.js 18
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Verificeer
docker --version
docker compose version
nginx -v
node -v
npm -v
```

**BELANGRIJK**: Log uit en weer in zodat Docker groep actief wordt!

```bash
exit
ssh user@192.168.178.116
docker ps  # Test zonder sudo
```

### 3. Initialiseer Git Repository in /var/www/nexa

```bash
# Maak directory aan
sudo mkdir -p /var/www/nexa
sudo chown -R $USER:www-data /var/www/nexa

# Ga naar directory
cd /var/www/nexa

# Initialiseer git
git init

# Voeg remote toe (vervang met jouw repo URL)
git remote add origin https://github.com/YOUR_USERNAME/nexa-cursor.git

# Fetch code
git fetch origin

# Checkout main branch
git checkout -b main origin/main

# Configureer git user
git config user.email "deployment@tosun.nl"
git config user.name "Deployment Server"

# Verificeer
git status
git log -1 --oneline
```

### 4. Setup .env File

```bash
cd /var/www/nexa/backend

# Copy .env.example naar .env
cp .env.example .env

# Edit .env
nano .env
```

**Belangrijke .env settings:**

```env
APP_NAME="NEXA Skillmatching"
APP_ENV=production
APP_KEY=  # Wordt automatisch gegenereerd
APP_DEBUG=false
APP_URL=http://192.168.178.116:8000

# Database (Docker PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=nexa
DB_USERNAME=nexa
DB_PASSWORD=CHANGE_THIS_PASSWORD  # ⚠️ Wijzig naar sterk wachtwoord!
```

### 5. Setup Docker Environment File

```bash
cd /var/www/nexa

# Maak .env voor docker-compose
nano .env
```

Voeg toe:
```env
DB_USERNAME=nexa
DB_PASSWORD=CHANGE_THIS_PASSWORD  # Zelfde als in backend/.env
DB_DATABASE=nexa
APP_URL=http://192.168.178.116:8000
```

### 6. Configureer Nginx

```bash
# Copy Nginx config
sudo cp /var/www/nexa/deploy/nginx-nexa.conf /etc/nginx/sites-available/nexa

# Edit voor jouw IP (optioneel, al ingesteld)
sudo nano /etc/nginx/sites-available/nexa

# Verwijder default site
sudo rm /etc/nginx/sites-enabled/default

# Enable nexa site
sudo ln -s /etc/nginx/sites-available/nexa /etc/nginx/sites-enabled/

# Test configuratie
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### 7. Setup GitHub Runner (Indien nog niet gedaan)

Zie [RUNNER-QUICK-START.md](RUNNER-QUICK-START.md) voor volledige instructies.

**Quick version:**
```bash
# Als github-runner user
sudo su - github-runner

# Download en configureer runner (zie GitHub voor token)
mkdir -p ~/actions-runner && cd ~/actions-runner
curl -o actions-runner.tar.gz -L https://github.com/actions/runner/releases/download/v2.311.0/actions-runner-linux-x64-2.311.0.tar.gz
tar xzf actions-runner.tar.gz

./config.sh --url https://github.com/YOUR_USERNAME/nexa-cursor --token YOUR_TOKEN

# Installeer als service
exit  # Terug naar normale user
cd /home/github-runner/actions-runner
sudo ./svc.sh install github-runner
sudo ./svc.sh start
```

### 8. Geef Runner Docker Rechten

```bash
# Update sudoers
sudo visudo -f /etc/sudoers.d/github-runner
```

Voeg toe:
```
github-runner ALL=(ALL) NOPASSWD: /usr/bin/cp
github-runner ALL=(ALL) NOPASSWD: /usr/bin/mkdir
github-runner ALL=(ALL) NOPASSWD: /usr/bin/chown
github-runner ALL=(ALL) NOPASSWD: /usr/bin/chmod
github-runner ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload nginx
github-runner ALL=(ALL) NOPASSWD: /usr/bin/systemctl restart nginx
```

---

## 🚀 Eerste Deployment (Handmatig Testen)

Voordat je de automatische deployment test, doe een handmatige deployment:

```bash
cd /var/www/nexa

# 1. Git pull
git pull origin main

# 2. Build assets
cd backend
npm ci
npm run build
cd ..

# 3. Build Docker containers
docker compose -f docker-compose.prod.yml build

# 4. Start containers
docker compose -f docker-compose.prod.yml up -d

# Wacht 10 seconden
sleep 10

# 5. Setup Laravel
docker compose -f docker-compose.prod.yml exec backend php artisan key:generate --force
docker compose -f docker-compose.prod.yml exec backend php artisan migrate --force
docker compose -f docker-compose.prod.yml exec backend php artisan config:cache
docker compose -f docker-compose.prod.yml exec backend php artisan route:cache
docker compose -f docker-compose.prod.yml exec backend php artisan view:cache
docker compose -f docker-compose.prod.yml exec backend php artisan storage:link

# 6. Set permissions
sudo chown -R $USER:www-data /var/www/nexa/backend/storage
sudo chown -R $USER:www-data /var/www/nexa/backend/bootstrap/cache
sudo chmod -R 775 /var/www/nexa/backend/storage
sudo chmod -R 775 /var/www/nexa/backend/bootstrap/cache

# 7. Reload Nginx
sudo systemctl reload nginx
```

### Verificatie

```bash
# Check Docker containers
docker compose -f /var/www/nexa/docker-compose.prod.yml ps

# Moet tonen:
# nexa_backend    Up      0.0.0.0:8000->8000/tcp
# nexa_db         Up      127.0.0.1:5432->5432/tcp
# nexa_n8n        Up      127.0.0.1:5678->5678/tcp

# Test applicatie
curl -I http://localhost:8000
# Moet 200 OK geven

# Test via Nginx
curl -I http://localhost
# Moet ook 200 OK geven

# Test vanuit browser
# Open: http://192.168.178.116:8000 (direct naar Docker)
# Open: http://192.168.178.116 (via Nginx)
```

---

## 🤖 Automatische Deployment

Na succesvolle handmatige setup, werkt automatische deployment zo:

### Trigger Deployment

```bash
# Optie 1: Push naar main
git push origin main

# Optie 2: Manual trigger via GitHub
# Ga naar: Repository → Actions → Deploy to Production → Run workflow
```

### Deployment Stappen (Automatisch)

De GitHub Actions workflow voert deze stappen uit:

1. **Git Pull** - Haalt laatste code op in `/var/www/nexa`
2. **Build Assets** - NPM install en build
3. **Build Docker** - Bouwt nieuwe Docker images
4. **Setup Laravel** - Start containers tijdelijk, run migrations en cache
5. **Set Permissions** - Zorgt voor juiste file permissions
6. **Start Container** - Start Docker containers op poort 8000
7. **Verify** - Test of applicatie bereikbaar is

### Monitor Deployment

Open meerdere terminals naar je server:

**Terminal 1 - Runner Logs:**
```bash
ssh user@192.168.178.116
sudo journalctl -u actions.runner.*.service -f
```

**Terminal 2 - Docker Logs:**
```bash
ssh user@192.168.178.116
docker compose -f /var/www/nexa/docker-compose.prod.yml logs -f
```

**Terminal 3 - Laravel Logs:**
```bash
ssh user@192.168.178.116
tail -f /var/www/nexa/backend/storage/logs/laravel.log
```

---

## 🔧 Veelgebruikte Commands

### Docker Management

```bash
cd /var/www/nexa

# Start containers
docker compose -f docker-compose.prod.yml up -d

# Stop containers
docker compose -f docker-compose.prod.yml down

# Restart containers
docker compose -f docker-compose.prod.yml restart

# View logs
docker compose -f docker-compose.prod.yml logs -f

# View logs voor specifieke container
docker compose -f docker-compose.prod.yml logs -f backend

# Check status
docker compose -f docker-compose.prod.yml ps

# Enter container
docker compose -f docker-compose.prod.yml exec backend bash
```

### Laravel Commands (in Container)

```bash
cd /var/www/nexa

# Run migrations
docker compose -f docker-compose.prod.yml exec backend php artisan migrate

# Clear cache
docker compose -f docker-compose.prod.yml exec backend php artisan cache:clear

# Clear config
docker compose -f docker-compose.prod.yml exec backend php artisan config:clear

# Cache config
docker compose -f docker-compose.prod.yml exec backend php artisan config:cache

# Tinker
docker compose -f docker-compose.prod.yml exec backend php artisan tinker
```

### Git Operations

```bash
cd /var/www/nexa

# Check status
git status

# Pull latest
git pull origin main

# View log
git log -5 --oneline

# Reset to specific commit
git reset --hard COMMIT_HASH
```

---

## 🐛 Troubleshooting

### Container draait niet

```bash
# Check logs
docker compose -f /var/www/nexa/docker-compose.prod.yml logs backend

# Rebuild container
cd /var/www/nexa
docker compose -f docker-compose.prod.yml build --no-cache backend
docker compose -f docker-compose.prod.yml up -d
```

### Port 8000 niet bereikbaar

```bash
# Check of container draait
docker ps | grep nexa_backend

# Check of port luistert
sudo netstat -tulpn | grep 8000

# Check firewall (als actief)
sudo ufw status
sudo ufw allow 8000/tcp
```

### Git pull faalt

```bash
cd /var/www/nexa

# Reset git state
git fetch origin
git reset --hard origin/main
git clean -fd

# Check remote
git remote -v
```

### Database connection failed

```bash
# Check of database container draait
docker ps | grep nexa_db

# Check database logs
docker compose -f /var/www/nexa/docker-compose.prod.yml logs db

# Test connectie
docker compose -f /var/www/nexa/docker-compose.prod.yml exec backend \
  php artisan tinker
>>> \DB::connection()->getPdo();
```

### Nginx 502 Bad Gateway

```bash
# Check Nginx config
sudo nginx -t

# Check of backend container draait
docker ps | grep nexa_backend

# Test poort 8000
curl -I http://localhost:8000

# Reload Nginx
sudo systemctl reload nginx
```

---

## 🔒 Security Checklist

- [ ] Database passwords gewijzigd in `.env` files
- [ ] Firewall geconfigureerd (optioneel voor lokaal netwerk)
  ```bash
  sudo ufw allow 22/tcp   # SSH
  sudo ufw allow 80/tcp   # HTTP
  sudo ufw allow 443/tcp  # HTTPS
  sudo ufw allow 8000/tcp # Docker direct access
  sudo ufw enable
  ```
- [ ] SSH key authentication (in plaats van wachtwoord)
- [ ] Reguliere backups geconfigureerd
- [ ] APP_DEBUG=false in productie
- [ ] Log rotatie ingesteld

---

## 📊 URLs Overzicht

| URL | Beschrijving |
|-----|--------------|
| `http://192.168.178.116:8000` | Direct naar Docker container |
| `http://192.168.178.116` | Via Nginx reverse proxy (port 80) |
| `http://192.168.178.116:5678` | N8N automation (indien enabled) |

---

## 🎉 Klaar!

Je deployment is nu operationeel! Elke push naar `main` triggert automatisch een deployment.

### Next Steps:
1. ✅ Test deployment met kleine code wijziging
2. ✅ Monitor logs tijdens eerste automatische deployment
3. ✅ Setup SSL certificaat (optioneel)
4. ✅ Configure backups
5. ✅ Setup monitoring

---

**Vragen of problemen?**
- Check [DOCKER-TROUBLESHOOTING.md](DOCKER-TROUBLESHOOTING.md)
- Check runner logs: `sudo journalctl -u actions.runner.*.service -f`
- Check Docker logs: `docker compose -f /var/www/nexa/docker-compose.prod.yml logs -f`

