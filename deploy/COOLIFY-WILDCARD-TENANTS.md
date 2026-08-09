# Coolify: automatische tenant-subdomeinen (nexasuite.nl)

Doel: `https://taxiroyaal.nexasuite.nl`, `https://anderetenant.nexasuite.nl`, … werken **zonder** elk subdomein handmatig in Coolify Domains te zetten.

## Waarom `https://*.nexasuite.nl` in Coolify kapot gaat

Coolify maakt dan TLS/HostSNI voor `*.nexasuite.nl`. Traefik weigert dat:

`HostSNI(\`*.nexasuite.nl\`) is not a valid hostname` → **No Available Server**.

Daarom: **nooit** `*.nexasuite.nl` (en geen losse tenants) in het Domains-veld.

Let op spaties: `taxiroyaal.nexasuite.nl ` (trailing space) faalt ook in HostSNI.

## Eenmalig: DNS + wildcard-certificaat

1. DNS: A-record `*.nexasuite.nl` → IP van de Coolify-server (naast apex `nexasuite.nl`).
2. Coolify → Server → **Proxy** (Traefik): DNS-challenge + wildcard-certificaat, zie [Coolify wildcard certs](https://coolify.io/docs/knowledge-base/proxy/traefik/wildcard-certs):

```yaml
- traefik.http.routers.traefik.tls.domains[0].main=nexasuite.nl
- traefik.http.routers.traefik.tls.domains[0].sans=*.nexasuite.nl
```

Proxy herstarten na opslaan. Wildcard-cert hoort op de **proxy**, niet als Domain op de app.

## Coolify applicatie (Docker Compose)

### Domains-veld

Alleen apex (en eventueel www), **met containerpoort 8000**:

```text
https://nexasuite.nl:8000,https://www.nexasuite.nl:8000
```

- **Geen** `taxiroyaal.nexasuite.nl`
- **Geen** `*.nexasuite.nl`
- **Geen** spaties achter hostnames

Subdomeinen komen via labels in `docker-compose.deploy.yml`.

### Traefik-regels (labels)

| Router | Rule | Priority | Service |
|--------|------|----------|---------|
| Apex/www | `Host(nexasuite.nl) \|\| Host(www…)` | `100` | `backend` |
| Tenants | `HostRegexp(\`^[a-z0-9-]+\.nexasuite\.nl$\`)` | `1` | `backend` |

**Niet doen:**

- Negative lookahead `(?!…)` in HostRegexp (Go/RE2 → parse error)
- `Host(\`*.nexasuite.nl\`)` als app-router (Coolify/Traefik HostSNI faalt)
- `traefik.docker.network=coolify` terwijl backend alleen op het **UUID-app-netwerk** zit

Backend en proxy delen het app-netwerk (bijv. `w4byop8qxc3xdrhdma9mqb9p`). Traefik pikt dat automatisch als de container maar op één netwerk hangt.

```bash
docker inspect coolify-proxy --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}'
docker inspect <backend> --format '{{range $k,$v := .NetworkSettings.Networks}}{{$k}}{{"\n"}}{{end}}'
# Gemeenschappelijk netwerk = UUID-netwerk, niet (alleen) coolify
```

Lage tenant-priority zodat Coolify-apps met exacte `Host(panel.nexasuite.nl)` / `n8n` / `automations` winnen.

### Als Coolify labels overschrijft

Zet in Coolify **Container Labels** op *readonly* / plak dezelfde labels uit `docker-compose.deploy.yml`.

## Checklist bij 503 / No Available Server

1. Domains = **alleen** `https://nexasuite.nl:8000,https://www.nexasuite.nl:8000`.
2. Proxy-logs: geen `HostSNI(\`*.nexasuite.nl\`)` en geen `(?!` HostRegexp-errors.
3. `docker inspect` backend: `nexa-saas-tenants-https.rule=HostRegexp(\`^[a-z0-9-]+\.nexasuite\.nl$\`)` en `service=backend`.
4. DNS `*.nexasuite.nl` → server; wildcard-cert op **proxy**.
5. App herstart / redeploy na label-wijziging; PROD deployt vanaf **main** (niet alleen `release/test`).
