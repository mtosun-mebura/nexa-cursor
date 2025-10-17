# NEXA CI/CD Deployment Setup

Deze gids helpt je bij het opzetten van een automatische deployment pipeline naar je server.

## 🚀 Overzicht

De CI/CD pipeline wordt automatisch getriggerd wanneer:
- Code wordt gemerged naar de `main` branch
- Handmatig via GitHub Actions interface

## 📋 Vereisten

### Server Setup (192.168.178.116)

1. **Server voorbereiden:**
   ```bash
   # Upload en run het setup script
   scp deploy/setup-server.sh user@192.168.178.116:/tmp/
   ssh user@192.168.178.116
   chmod +x /tmp/setup-server.sh
   sudo /tmp/setup-server.sh
   ```

2. **Database configureren:**
   ```bash
   # MySQL/MariaDB installeren
   sudo apt install mysql-server
   sudo mysql_secure_installation
   
   # Database en gebruiker aanmaken
   sudo mysql -e "CREATE DATABASE nexa;"
   sudo mysql -e "CREATE USER 'nexa_user'@'localhost' IDENTIFIED BY 'your_secure_password';"
   sudo mysql -e "GRANT ALL PRIVILEGES ON nexa.* TO 'nexa_user'@'localhost';"
   sudo mysql -e "FLUSH PRIVILEGES;"
   ```

3. **SSH Key Setup:**
   ```bash
   # SSH key genereren voor deployment
   ssh-keygen -t rsa -b 4096 -C "github-actions@nexa-cursor" -f ~/.ssh/github_actions
   
   # Public key toevoegen aan authorized_keys
   cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys
   
   # Private key kopiëren voor GitHub secrets
   cat ~/.ssh/github_actions
   ```

## 🔐 GitHub Secrets Configuratie

**Stap 1:** Ga naar je GitHub repository (niet je profiel settings!)
**Stap 2:** Klik op de "Settings" tab in je repository
**Stap 3:** Scroll naar beneden naar "Secrets and variables" in de linker sidebar
**Stap 4:** Klik op "Actions"

Voeg de volgende secrets toe:

| Secret Name | Waarde | Beschrijving |
|-------------|--------|--------------|
| `DEPLOY_HOST` | `192.168.178.116` | Server IP adres |
| `DEPLOY_USER` | `your_username` | SSH gebruikersnaam |
| `DEPLOY_KEY` | `-----BEGIN OPENSSH PRIVATE KEY-----...` | Volledige private SSH key |
| `DEPLOY_PORT` | `22` | SSH poort (optioneel, default 22) |

## 🔧 Environment Configuratie

1. **Server .env bestand:**
   ```bash
   # Op de server
   cd /var/www/nexa
   cp .env.example .env
   nano .env
   ```

2. **Belangrijke configuraties:**
   ```env
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=http://192.168.178.116
   DB_DATABASE=nexa
   DB_USERNAME=nexa_user
   DB_PASSWORD=your_secure_password
   ```

3. **Application key genereren:**
   ```bash
   php artisan key:generate
   ```

## 🚀 Deployment Proces

### Automatische Deployment

1. **Code committen naar main branch:**
   ```bash
   git add .
   git commit -m "Feature: nieuwe functionaliteit"
   git push origin main
   ```

2. **Pipeline wordt automatisch getriggerd:**
   - Code wordt gecheckt
   - Dependencies worden geïnstalleerd
   - Assets worden gebouwd
   - Code wordt gedeployed naar server
   - Laravel caches worden geleegd
   - Database migrations worden uitgevoerd

### Handmatige Deployment

1. Ga naar GitHub → Actions
2. Selecteer "Deploy to Production"
3. Klik "Run workflow"

## 📁 Deployment Structuur

```
/var/www/nexa/
├── app/                 # Laravel applicatie
├── bootstrap/          # Bootstrap bestanden
├── config/             # Configuratie bestanden
├── database/           # Database bestanden
├── public/             # Web root
├── resources/          # Views, assets
├── routes/             # Route definities
├── storage/            # Logs, cache, sessions
├── vendor/             # Composer dependencies
├── .env                # Environment configuratie
└── artisan             # Laravel CLI
```

## 🔍 Troubleshooting

### Veelvoorkomende Problemen

1. **SSH Connectie Falen:**
   ```bash
   # Test SSH connectie
   ssh -i ~/.ssh/github_actions user@192.168.178.116
   ```

2. **Permission Errors:**
   ```bash
   # Fix permissions
   sudo chown -R www-data:www-data /var/www/nexa
   sudo chmod -R 755 /var/www/nexa
   sudo chmod -R 775 /var/www/nexa/storage
   ```

3. **Database Connectie:**
   ```bash
   # Test database connectie
   php artisan tinker
   DB::connection()->getPdo();
   ```

4. **Nginx Configuratie:**
   ```bash
   # Test nginx config
   sudo nginx -t
   sudo systemctl reload nginx
   ```

### Logs Bekijken

```bash
# GitHub Actions logs
# Ga naar GitHub → Actions → Selecteer workflow run

# Server logs
tail -f /var/log/nginx/error.log
tail -f /var/www/nexa/storage/logs/laravel.log
```

## 🔄 Rollback Procedure

Als er problemen zijn na deployment:

```bash
# Terug naar vorige versie
cd /var/www/nexa
sudo cp -r /var/www/nexa-backup-YYYYMMDD-HHMMSS/* .

# Laravel caches legen
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Services herstarten
sudo systemctl reload nginx
sudo systemctl reload php8.2-fpm
```

## 📞 Support

Voor vragen of problemen:
1. Check GitHub Actions logs
2. Check server logs
3. Test SSH connectie
4. Verify GitHub secrets configuratie
