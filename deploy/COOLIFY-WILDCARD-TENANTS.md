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

Subdomeinen komen via labels in `docker-compose.deploy.yml` (`HostRegexp`).

### Labels (al in de compose)

Op service `backend` staan o.a.:

- `HostRegexp(\`^.+\.nexasuite\.nl$\`)` voor alle tenant-subdomeinen
- `loadbalancer.server.port=8000`

Na deploy/restart van de stack moeten nieuwe tenants meteen bereikbaar zijn (DNS + cert zijn al wildcard).

### Als Coolify labels overschrijft

Zet in Coolify **Container Labels** op *readonly* / plak dezelfde labels uit `docker-compose.deploy.yml`, of laat Domains leeg en gebruik alleen de SaaS-labels (Coolify-docs: “SaaS — route every subdomain to one application”).

## Checklist bij 503 op een subdomein

1. Domains bevat **geen** `*.nexasuite.nl`.
2. `docker inspect` op de backend-container: labels met `HostRegexp` en `loadbalancer.server.port=8000`.
3. DNS `*.nexasuite.nl` wijst naar de server.
4. Wildcard-cert (`*.nexasuite.nl`) staat op de proxy.
5. Applicatie herstart na domain/label-wijziging.
