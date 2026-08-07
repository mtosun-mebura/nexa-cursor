# Coolify: automatische tenant-subdomeinen (nexasuite.nl)

Doel: `https://taxiroyaal.nexasuite.nl`, `https://anderetenant.nexasuite.nl`, … werken **zonder** elk subdomein handmatig in Coolify Domains te zetten.

## Waarom `https://*.nexasuite.nl` in Coolify kapot gaat

Coolify maakt dan een Traefik-regel `Host(\`*.nexasuite.nl\`)`. Traefik v3 weigert wildcards in `Host()` → router registreert niet → **503**.  
Daarom: **nooit** `*.nexasuite.nl` in het Domains-veld.

## Eenmalig: DNS + wildcard-certificaat

1. DNS: A-record `*.nexasuite.nl` → IP van de Coolify-server (naast apex `nexasuite.nl`).
2. Coolify → Server → **Proxy** (Traefik): DNS-challenge + wildcard-certificaat, zie [Coolify wildcard certs](https://coolify.io/docs/knowledge-base/proxy/traefik/wildcard-certs):

```yaml
- traefik.http.routers.traefik.tls.domains[0].main=nexasuite.nl
- traefik.http.routers.traefik.tls.domains[0].sans=*.nexasuite.nl
```

Proxy herstarten na opslaan.

## Coolify applicatie (Docker Compose)

### Domains-veld

Alleen apex (en eventueel www), **met containerpoort 8000**:

```text
https://nexasuite.nl:8000,https://www.nexasuite.nl:8000
```

- **Geen** `taxiroyaal.nexasuite.nl`
- **Geen** `*.nexasuite.nl`

Subdomeinen komen via labels in `docker-compose.deploy.yml`.

**Belangrijk (Traefik/Go-RE2):** geen negative lookahead `(?!…)` in `HostRegexp` — dat faalt stil en geeft **no available server / 503** op tenants.

Aanpak:

| Router | Rule | Priority |
|--------|------|----------|
| Apex/www | `Host(nexasuite.nl) \|\| Host(www…)` | `100` |
| Tenants | `Host(\`*.nexasuite.nl\`)` | `1` |

Lage tenant-priority zodat Coolify-apps met exacte `Host(panel.nexasuite.nl)` / `n8n` / `automations` winnen.

**Reserved hosts** (eigen Coolify/andere service, niet handmatig in SaaS-Domains):

| Subdomein | Doel |
|-----------|------|
| `panel.nexasuite.nl` | Coolify dashboard |
| `n8n.nexasuite.nl` | n8n (legacy) |
| `automations.nexasuite.nl` | n8n / automations |

### Labels (al in de compose)

- Apex/www: priority `100`, poort `8000`
- Tenants: `Host(\`*.nexasuite.nl\`)` priority `1`, poort `8000`

Na deploy/restart van de stack moeten nieuwe tenants meteen bereikbaar zijn (DNS + cert zijn al wildcard).

### Als Coolify labels overschrijft

Zet in Coolify **Container Labels** op *readonly* / plak dezelfde labels uit `docker-compose.deploy.yml`, of laat Domains leeg en gebruik alleen de SaaS-labels (Coolify-docs: “SaaS — route every subdomain to one application”).

## Checklist bij 503 op een subdomein

1. Domains bevat **geen** `*.nexasuite.nl`.
2. `docker inspect` op de backend-container: labels met `HostRegexp` en `loadbalancer.server.port=8000`.
3. DNS `*.nexasuite.nl` wijst naar de server.
4. Wildcard-cert (`*.nexasuite.nl`) staat op de proxy.
5. Applicatie herstart na domain/label-wijziging.
