# 🔐 GitHub Secrets Setup Gids

## Waar vind je GitHub Secrets?

### Stap 1: Ga naar je Repository
1. Ga naar je GitHub repository (bijv. `https://github.com/username/nexa-cursor`)
2. **NIET** naar je profiel settings!

### Stap 2: Repository Settings
1. Klik op de **"Settings"** tab in je repository
2. Dit staat naast "Code", "Issues", "Pull requests", etc.

### Stap 3: Secrets and Variables
1. Scroll naar beneden in de linker sidebar
2. Zoek naar **"Secrets and variables"** sectie
3. Klik op **"Actions"**

### Stap 4: Nieuwe Secret Toevoegen
1. Klik op **"New repository secret"**
2. Voer de naam in (bijv. `DEPLOY_HOST`)
3. Voer de waarde in (bijv. `192.168.178.116`)
4. Klik **"Add secret"**

## Vereiste Secrets

Voeg deze 3 secrets toe:

| Secret Name | Waarde | Voorbeeld |
|-------------|--------|-----------|
| `DEPLOY_HOST` | Server IP | `192.168.178.116` |
| `DEPLOY_USER` | SSH gebruikersnaam | `ubuntu` of `root` |
| `DEPLOY_KEY` | SSH private key | `-----BEGIN OPENSSH PRIVATE KEY-----...` |

## SSH Key Genereren

```bash
# Op je server
ssh-keygen -t rsa -b 4096 -C "github-actions@nexa-cursor" -f ~/.ssh/github_actions
cat ~/.ssh/github_actions.pub >> ~/.ssh/authorized_keys
cat ~/.ssh/github_actions  # Kopieer deze volledige output
```

## Troubleshooting

**"Secrets and variables" niet gevonden?**
- Zorg dat je in je **repository** bent, niet je profiel
- Controleer of je admin rechten hebt op de repository
- Probeer de pagina te refreshen

**SSH Key problemen?**
```bash
# Test SSH connectie
ssh -i ~/.ssh/github_actions user@192.168.178.116
```

**Deployment faalt?**
- Check GitHub Actions logs
- Verify alle secrets zijn correct ingesteld
- Test SSH connectie handmatig
