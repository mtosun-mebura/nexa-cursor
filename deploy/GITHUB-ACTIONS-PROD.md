# GitHub Actions → PROD (AWS Lightsail)

Workflow: `.github/workflows/deploy-prod.yml`

## Secrets & variables

| Naam | Type | Beschrijving |
|------|------|--------------|
| `AWS_HOST` | Secret | Publiek IP of hostname van de Lightsail-instance |
| `AWS_USER` | Secret | SSH-user (bijv. `ubuntu`) |
| `AWS_SSH_KEY` | Secret | Private key (OpenSSH-formaat, volledige inhoud) |
| `AWS_SSH_PORT` | Secret | Optioneel; standaard `22` |
| `APP_DIR` | Variable | Git-repo op de server (bijv. `/home/ubuntu/nexasuite`) |

## Veelvoorkomende fout: `dial tcp …:22: i/o timeout`

De log toont vaak eerst:

```text
tar all files into /tmp/….tar.gz
scp file to server.
dial tcp ***:22: i/o timeout
```

Dat betekent **niet** dat het deploy-bestand ontbreekt op de runner. Het script `nexa-deploy-tenant-ci.sh` is wel aangemaakt; de **SSH-verbinding naar Lightsail lukt niet**.

### Oplossing (Lightsail)

1. **Instance draait** — Lightsail console → instance status *Running*.
2. **`AWS_HOST`** — moet het **publieke** IP zijn (Networking-tab), niet het private IP.
3. **IPv4 firewall** — Lightsail → instance → **Networking** → **IPv4 firewall**:
   - Regel **SSH (22)** toevoegen of aanpassen.
   - GitHub-hosted runners (`ubuntu-latest`) hebben **dynamische IP-adressen**.
   - Voor test: bron `0.0.0.0/0` (alle IP's) op poort 22 — daarna deploy opnieuw proberen.
   - Voor productie: IP-ranges van GitHub ophalen via `https://api.github.com/meta` (veld `actions`) en die ranges in de firewall zetten, **of** een self-hosted runner in hetzelfde netwerk gebruiken.
4. **SSH lokaal testen** (vanaf je Mac):

   ```bash
   ssh -i /pad/naar/key -p 22 ubuntu@JOUW_LIGHTSAIL_IP "echo ok"
   ```

5. **Afwijkende SSH-poort** — secret `AWS_SSH_PORT` zetten en dezelfde poort in de Lightsail-firewall openzetten.

## Fout: bestand ontbreekt op server (`/tmp/nexa-deploy-tenant-ci.sh`)

Alleen relevant **na** een geslaagde SCP-upload. Als SCP al met timeout faalt, kom je deze stap niet tegen.

Na geslaagde upload controleert de workflow of `/tmp/nexa-deploy-tenant-ci.sh` op de server staat.

## Fout: `unable to unlink old 'backend/storage/.../.gitignore': Permission denied`

De Laravel-container (`www-data`) schrijft naar `backend/storage` en `backend/bootstrap/cache`. Bij `git checkout` kan de deploy-user die bestanden niet overschrijven.

**Eenmalig op Lightsail (als root of met sudo):**

```bash
sudo bash /home/ubuntu/nexasuite/deploy/fix-git-ownership.sh --user ubuntu --dir /home/ubuntu/nexasuite
```

Of handmatig:

```bash
sudo chown -R ubuntu:ubuntu /home/ubuntu/nexasuite/backend/storage /home/ubuntu/nexasuite/backend/bootstrap/cache
```

Daarna **Deploy PROD** opnieuw starten. Vanaf deploy-tenant.sh op main stopt het script eerst de backend-container en chown't storage **vóór** `git checkout`.

## Handmatig deployen (fallback)

Op de Lightsail-server, in `APP_DIR`:

```bash
export GIT_REF=v1.0.0   # jouw tag
export TENANT_DIR=/home/ubuntu/nexasuite
bash deploy/deploy-tenant.sh
```
