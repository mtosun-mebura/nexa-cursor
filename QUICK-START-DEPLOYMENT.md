# 🚀 Quick Start: NEXA CI/CD Setup

## Snelle Setup (5 minuten)

### 1. Server Voorbereiden
```bash
# SSH naar je server
ssh user@192.168.178.116

# Run setup script
curl -sSL https://raw.githubusercontent.com/your-repo/nexa-cursor/main/deploy/setup-server.sh | sudo bash
```

### 2. SSH Key Genereren
```bash
# Op je server
ssh-keygen -t rsa -b 4096 -C "github-actions@nexa" -f ~/.ssh/github_actions
cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys
cat ~/.ssh/github_actions  # Kopieer deze output
```

### 3. GitHub Secrets Instellen
Ga naar: `GitHub Repository → Settings → Secrets and variables → Actions`

Voeg toe:
- `DEPLOY_HOST`: `192.168.178.116`
- `DEPLOY_USER`: `je_gebruikersnaam`
- `DEPLOY_KEY`: `-----BEGIN OPENSSH PRIVATE KEY-----...` (volledige private key)

### 4. Database Setup
```bash
# Op server
sudo mysql -e "CREATE DATABASE nexa;"
sudo mysql -e "CREATE USER 'nexa_user'@'localhost' IDENTIFIED BY 'sterk_wachtwoord';"
sudo mysql -e "GRANT ALL PRIVILEGES ON nexa.* TO 'nexa_user'@'localhost';"
```

### 5. Environment Configureren
```bash
# Op server
cd /var/www/nexa
cp .env.example .env
nano .env  # Configureer database en andere settings
php artisan key:generate
```

## ✅ Klaar!

Nu wordt bij elke merge naar `main` automatisch gedeployed naar `192.168.178.116:/var/www/nexa`

### Test Deployment
1. Maak een PR aan
2. Merge de PR naar main
3. Check GitHub Actions tab
4. Bezoek `http://192.168.178.116`

## 🔧 Troubleshooting

**SSH Problemen:**
```bash
ssh -i ~/.ssh/github_actions user@192.168.178.116
```

**Permission Errors:**
```bash
sudo chown -R www-data:www-data /var/www/nexa
sudo chmod -R 775 /var/www/nexa/storage
```

**Nginx Restart:**
```bash
sudo systemctl reload nginx
sudo systemctl reload php8.2-fpm
```
