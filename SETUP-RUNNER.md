# GitHub Self-Hosted Runner Setup Guide

Deze guide helpt je om een self-hosted GitHub Actions runner op te zetten op je server (192.168.178.116).

## Stap 1: Bereid je server voor

SSH naar je server:
```bash
ssh user@192.168.178.116
```

## Stap 2: Installeer vereiste packages

```bash
# Update systeem
sudo apt update && sudo apt upgrade -y

# Installeer vereiste dependencies
sudo apt install -y curl git jq libicu-dev
```

## Stap 3: Maak een runner gebruiker aan (optioneel maar aanbevolen)

```bash
# Maak een dedicated user voor de runner
sudo useradd -m -s /bin/bash github-runner
sudo usermod -aG sudo github-runner

# Geef de runner user rechten voor www-data directory
sudo chown -R github-runner:www-data /var/www/nexa
sudo chmod -R 775 /var/www/nexa

# Switch naar de runner user
sudo su - github-runner
```

## Stap 4: Download en configureer de GitHub Runner

### 4.1: Ga naar je GitHub repository
1. Open: https://github.com/YOUR_USERNAME/nexa-cursor
2. Ga naar **Settings** → **Actions** → **Runners**
3. Klik op **New self-hosted runner**
4. Selecteer **Linux** en **x64**

### 4.2: Volg de GitHub instructies

GitHub zal je exacte commands geven, maar het ziet er ongeveer zo uit:

```bash
# Maak een directory voor de runner
mkdir -p ~/actions-runner && cd ~/actions-runner

# Download de laatste runner
curl -o actions-runner-linux-x64-2.311.0.tar.gz -L https://github.com/actions/runner/releases/download/v2.311.0/actions-runner-linux-x64-2.311.0.tar.gz

# Extract
tar xzf ./actions-runner-linux-x64-2.311.0.tar.gz

# Configureer de runner
./config.sh --url https://github.com/YOUR_USERNAME/nexa-cursor --token YOUR_TOKEN_FROM_GITHUB
```

**BELANGRIJK**: Gebruik de exacte URL en token die GitHub je geeft!

### 4.3: Configuratie vragen

Bij het configureren word je het volgende gevraagd:

```
Enter the name of the runner [default: hostname]: nexa-production
Enter any additional labels []: production,deploy
Enter name of work folder [default: _work]: _work
```

## Stap 5: Installeer de runner als service

```bash
# Installeer als systemd service
sudo ./svc.sh install github-runner

# Start de service
sudo ./svc.sh start

# Check status
sudo ./svc.sh status
```

## Stap 6: Configureer sudo rechten voor de runner

De runner heeft sudo rechten nodig voor sommige deployment taken:

```bash
# Voeg github-runner toe aan sudoers met NOPASSWD voor specifieke commands
sudo visudo -f /etc/sudoers.d/github-runner
```

Voeg deze regels toe:
```
github-runner ALL=(ALL) NOPASSWD: /usr/bin/cp
github-runner ALL=(ALL) NOPASSWD: /usr/bin/mkdir
github-runner ALL=(ALL) NOPASSWD: /usr/bin/chown
github-runner ALL=(ALL) NOPASSWD: /usr/bin/chmod
github-runner ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload nginx
github-runner ALL=(ALL) NOPASSWD: /usr/bin/systemctl reload php*-fpm
github-runner ALL=(ALL) NOPASSWD: /usr/bin/systemctl is-active
```

## Stap 7: Configureer Git in /var/www/nexa

```bash
# Als /var/www/nexa nog geen git repository is:
cd /var/www/nexa

# Initialiseer git
sudo -u github-runner git init

# Voeg remote toe
sudo -u github-runner git remote add origin https://github.com/YOUR_USERNAME/nexa-cursor.git

# Fetch en checkout main branch
sudo -u github-runner git fetch origin
sudo -u github-runner git checkout -b main origin/main

# Configureer git user (voor merge commits)
sudo -u github-runner git config user.email "github-runner@tosun.nl"
sudo -u github-runner git config user.name "GitHub Runner"
```

## Stap 8: Test de runner

### 8.1: Check of runner actief is
In je GitHub repository:
1. Ga naar **Settings** → **Actions** → **Runners**
2. Je zou een groene dot moeten zien naast je runner naam

### 8.2: Test deployment
Trigger een deployment:
```bash
# Optie 1: Push naar main branch
git push origin main

# Optie 2: Manuele trigger
# Ga naar GitHub → Actions → Deploy to Production → Run workflow
```

## Stap 9: Troubleshooting

### Runner start niet
```bash
# Check logs
sudo journalctl -u actions.runner.*.service -f

# Of check de runner logs
cd ~/actions-runner
tail -f _diag/*.log
```

### Permission errors
```bash
# Fix ownership
sudo chown -R github-runner:www-data /var/www/nexa
sudo chmod -R 775 /var/www/nexa/backend/storage
sudo chmod -R 775 /var/www/nexa/backend/bootstrap/cache
```

### Composer/NPM niet gevonden
```bash
# Zorg dat github-runner user composer en npm kan gebruiken
sudo -u github-runner which composer
sudo -u github-runner which npm

# Als niet gevonden, voeg toe aan PATH:
echo 'export PATH="$PATH:/usr/local/bin"' >> ~/.bashrc
source ~/.bashrc
```

### Git pull fails
```bash
# Reset git state in /var/www/nexa
cd /var/www/nexa
sudo -u github-runner git fetch origin
sudo -u github-runner git reset --hard origin/main
sudo -u github-runner git clean -fd
```

## Stap 10: Herstart runner service

Als je wijzigingen maakt, herstart de runner:

```bash
cd ~/actions-runner
sudo ./svc.sh stop
sudo ./svc.sh start
sudo ./svc.sh status
```

## Snel commando overzicht

```bash
# Runner status
sudo systemctl status actions.runner.*.service

# Runner logs
sudo journalctl -u actions.runner.*.service -f

# Runner herstarten
cd ~/actions-runner && sudo ./svc.sh restart

# Check runner in GitHub
# Settings → Actions → Runners
```

## Security Tips

1. **Gebruik een dedicated user**: De `github-runner` user heeft beperkte rechten
2. **Beperk sudo**: Alleen specifieke commands toegestaan via sudoers
3. **Firewall**: Zorg dat alleen jouw netwerk toegang heeft tot 192.168.178.116
4. **SSH Keys**: Gebruik SSH keys in plaats van wachtwoorden
5. **Updates**: Houd de runner software up-to-date

## Volgende stappen

Na setup:
1. ✅ Test een deployment via GitHub Actions
2. ✅ Monitor de logs voor errors
3. ✅ Stel monitoring/alerts in (optioneel)
4. ✅ Maak backup strategy voor /var/www/nexa

---

**Hulp nodig?**
- GitHub Actions Logs: Repository → Actions → Klik op een workflow run
- Runner Logs: `sudo journalctl -u actions.runner.*.service -f`
- Server Logs: `/var/www/nexa/backend/storage/logs/laravel.log`


