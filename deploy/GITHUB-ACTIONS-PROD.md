# GitHub Actions → PROD (Hostinger VPS)

Workflow: `.github/workflows/deploy-prod.yml`

## Secrets & variables

| Naam | Type | Beschrijving |
|------|------|--------------|
| `HOSTINGER_HOST` | Secret | Publiek IP of hostname van de VPS |
| `HOSTINGER_USER` | Secret | SSH-user (bijv. `ubuntu`) |
| `HOSTINGER_SSH_KEY` | Secret | Private key (OpenSSH-formaat, volledige inhoud) |
| `HOSTINGER_SSH_PORT` | Secret | Optioneel; standaard `22` |
| `APP_DIR` | Variable | Git-repo op de server (bijv. `/home/ubuntu/nexasuite`) |

### Legacy (nog ondersteund)

Oudere omgevingen kunnen nog `AWS_HOST`, `AWS_USER`, `AWS_SSH_KEY` en `AWS_SSH_PORT` gebruiken. De workflow valt daarop terug als de `HOSTINGER_*` secrets niet zijn ingesteld.

## Veelvoorkomende fout: `dial tcp …:22: i/o timeout`

De log toont vaak eerst:

```text
tar all files into /tmp/….tar.gz
scp file to server.
dial tcp ***:22: i/o timeout
```

Dat betekent **niet** dat het deploy-bestand ontbreekt op de runner. Het script `nexa-deploy-tenant-ci.sh` is wel aangemaakt; de **SSH-verbinding naar de VPS lukt niet**.

### Oplossing (Hostinger VPS)

1. **VPS draait** — Hostinger hPanel → VPS status *Running*.
2. **`HOSTINGER_HOST`** — moet het **publieke** IP zijn, niet een privé- of LAN-adres.
3. **Firewall** — zowel op de VPS (UFW) als in Hostinger hPanel:
   - Regel **SSH (22)** toevoegen of aanpassen.
   - GitHub-hosted runners (`ubuntu-latest`) hebben **dynamische IP-adressen**.
   - Voor test: bron `0.0.0.0/0` (alle IP's) op poort 22 — daarna deploy opnieuw proberen.
   - Voor productie: IP-ranges van GitHub ophalen via `https://api.github.com/meta` (veld `actions`) en die ranges in de firewall zetten.
4. **SSH lokaal testen** (vanaf je Mac):

   ```bash
   ssh -i /pad/naar/key -p 22 ubuntu@JOUW_VPS_IP "echo ok"
   ```

5. **Afwijkende SSH-poort** — secret `HOSTINGER_SSH_PORT` zetten en dezelfde poort in UFW + hPanel openzetten.

## Fout: bestand ontbreekt op server (`/tmp/nexa-deploy-tenant-ci.sh`)

Alleen relevant **na** een geslaagde SCP-upload. Als SCP al met timeout faalt, kom je deze stap niet tegen.

Na geslaagde upload controleert de workflow of `/tmp/nexa-deploy-tenant-ci.sh` op de server staat.

## Fout: `Run Command Timeout` na ~10 minuten (bijv. tijdens `vite build`)

`appleboy/ssh-action` heeft standaard **`command_timeout: 10m`**. Een volledige PROD-deploy (git, `npm ci`, Vite, Docker build, migrate) duurt op een VPS vaak langer.

De workflow zet `command_timeout: 90m` op de deploy-stap (zelfde als `timeout-minutes` van de job).

Als het tóch lang duurt op een kleine instance: controleer geheugen (`free -h`) tijdens deploy — bij swap kan Vite lijken te hangen op `computing gzip size`.

## Fout: `unable to unlink old 'backend/storage/.../.gitignore': Permission denied`

De Laravel-container (`www-data`) schrijft naar `backend/storage` en `backend/bootstrap/cache`. Bij `git checkout` kan de deploy-user die bestanden niet overschrijven.

**Eenmalig op de VPS (als root of met sudo):**

```bash
sudo bash /home/ubuntu/nexasuite/deploy/fix-git-ownership.sh --user ubuntu --dir /home/ubuntu/nexasuite
```

Of handmatig:

```bash
sudo chown -R ubuntu:ubuntu /home/ubuntu/nexasuite/backend/storage /home/ubuntu/nexasuite/backend/bootstrap/cache
```

Daarna **Deploy PROD** opnieuw starten. Vanaf deploy-tenant.sh op main stopt het script eerst de backend-container en chown't storage **vóór** `git checkout`.

## Fout: `413 Request Entity Too Large` (tenant-ZIP import)

Nginx op de host weigert de upload **vóór** Laravel (standaard ~1 MB). Geldt voor alle tenant-subdomeinen (`taxiroyaal.nexasuite.nl`, enz.).

**Eenmalig op de VPS:**

```bash
cd /home/ubuntu/nexasuite   # of jouw APP_DIR
sudo bash deploy/fix-nginx-upload-limit.sh
```

Of handmatig in het `server`-block van de site (bijv. `/etc/nginx/sites-available/nexasuite`):

```nginx
client_max_body_size 512M;
proxy_read_timeout 300;
proxy_send_timeout 300;
```

Daarna: `sudo nginx -t && sudo systemctl reload nginx`.

Zie ook `deploy/UPLOAD-LIMITS.md`. Laravel staat tot ~500 MB toe (`config/upload.php`); nginx moet minstens even groot zijn.

## Handmatig deployen (fallback)

Op de VPS, in `APP_DIR`:

```bash
export GIT_REF=v1.0.0   # jouw tag
export TENANT_DIR=/home/ubuntu/nexasuite
export DEPLOY_USER=ubuntu
bash deploy/deploy-tenant.sh
```
