# Database & omgeving (Nexa)

## Waar staat het schema / welke `.env` telt?

| Situatie | Bestand | Opmerking |
|----------|---------|-----------|
| **`php artisan`**, HTTP via `public/index.php` | **`backend/.env`** | Laravel `base_path()` is de map **`backend`**. Alleen dit bestand wordt automatisch ingeladen door het framework. |
| **Projectroot** (monorepo) | **`.env` in repo-root** (optioneel) | Wordt **niet** door Laravel gelezen, tenzij je zelf symlinkt of `DOTENV_PATH` zet. Handig voor Docker Compose op root-niveau. |
| **Voorbeeldwaarden** | **`backend/.env.example`** | Kopieer naar `backend/.env` en vul aan. |

### Eén `.env` voor alles (aanbevolen)

1. Houd **één bron**: bijvoorbeeld `backend/.env`.
2. Vanaf de **repo-root** (optioneel):

   ```bash
   ln -sf backend/.env .env
   ```

   Dan hebben tools in de root hetzelfde bestand als Laravel.

3. Sommige services lezen **extra** keys uit **root** `.env` (buiten Laravel), o.a. `GOOGLE_MAPS_API_KEY` via `EnvService::getRootEnvPath()` — zie code. Wil je echt alles op één plek: zet die keys ook in `backend/.env` **of** symlink zodat er maar één fysiek bestand is.

**Modules** (Skillmatching, Nexa Taxi) gebruiken **dezelfde** `backend/.env` en `config/database.php`. Standaard (`MODULE_DATABASE_STRATEGY=schema`): één database, per module een PostgreSQL-schema (`nexa_taxi`, …) en connection `module_*`.

---

## PostgreSQL in Docker (Compose)

| Bestand | Rol |
|---------|-----|
| `docker-compose.postgres.yml` | Service `db` (PostgreSQL 16), volume `nexa_postgres_data` |
| `docker-compose.yml` | Lokaal: `db` + `backend` |
| `docker-compose.deploy.yml` | TEST (Proxmox) + PROD (AWS): `db` + `backend` |

In **repo-root `.env`** (gemount in de backend-container):

```env
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=nexa
DB_USERNAME=nexa
DB_PASSWORD=<sterk wachtwoord>
```

`POSTGRES_*` in de database-container komt uit dezelfde `DB_*`-variabelen. Compose start `db` vóór `backend` (`depends_on` + healthcheck).

**Lokaal:** `docker compose up -d` — app op http://localhost:8085, Postgres op `127.0.0.1:5432` (alleen host, niet publiek).

**Deploy:** `.github/workflows/deploy.yml` en `deploy/deploy-tenant.sh` wachten op `pg_isready` vóór migraties. Gebruik **geen** `docker system prune --volumes` op servers met data in `nexa_postgres_data`.

Migratie van een **externe** Postgres: dump/restore naar de nieuwe container (eenmalig), daarna `DB_HOST=db` in `.env`.

---

## Module-database strategie

In **`backend/.env`** (standaard):

```env
MODULE_DATABASE_STRATEGY=schema
```

- **schema** (aanbevolen): één database (`DB_DATABASE`), kern in `public`, module-tabellen in schema's `nexa_taxi`, `nexa_skillmatching`, …
- **database** (legacy): aparte PostgreSQL-databases `nexa_taxi`, …
- **single**: alles in `public` (niet gebruikt op nieuwe omgevingen)

Na module-installatie: `php artisan modules:ensure-databases`.

---

## Migraties: waar staan ze?

| Locatie | Rol |
|---------|-----|
| `database/migrations/2026_04_20_000001_install_nexa_application_schema.php` | **Bundel:** roept `App\Database\Pre2026Baseline` aan (één migratie-record). |
| `app/Database/Pre2026Baseline.php` | Geconsolideerde pre-2026-baseline (historische `up()`-stappen; opnieuw te bouwen met `scripts/build-pre2026-baseline.php` als je ooit weer losse bronbestanden hebt). |
| `database/migrations/*.php` (anders dan de bundel) | Nieuwe wijzigingen **na** deze refactor. |

**Install / update schema:**

```bash
cd backend
php artisan migrate
```

`php artisan migrate:all` is een alias voor `migrate`.

**PostgreSQL/MySQL:** de bundel verzamelt core, shared en module-mappen en sorteert alles op **bestandsnaam** (zelfde effect als vroeger gemengde paths). De bundel draait **buiten** één PostgreSQL-transactie (`withinTransaction = false`) zodat een DDL-fout niet alle volgende stappen in `25P02` laat eindigen.  
**SQLite (PHPUnit):** alleen core + shared (module-archief sla je over vanwege pgsql-specifieke SQL).

**Module-install** kopieert nog steeds bestanden naar `database/migrations/modules/{naam}/` voor **incrementele** module-migraties op de standaard-DB (`ModuleMigrationPathResolver`). Per-module **eigen databases** gebruiken `Pre2026Baseline` gefilterd op set (core/shared/module).

**Bestaande productie-DB** met al ~honderd rijen in `migrations`: deploy van deze structuur vereist een **bewuste** keuze (nieuwe DB + dump, of handmatig de bundel als “al uitgevoerd” markeren zonder `up()`). Zie `docs/MIGRATION_SQUASH.md`.

---

## Nieuwe omgeving opzetten

```bash
cd backend
cp .env.example .env
php artisan key:generate
# vul DB_* in .env
php artisan migrate
php artisan db:seed   # indien gewenst
```

Bij **`MODULE_DATABASE_STRATEGY=schema`** volstaat `php artisan migrate` op de hoofd-DB; module-schema's worden bij install/ensure-databases ingericht.

---

## Zie ook

- [MIGRATION_SQUASH.md](./MIGRATION_SQUASH.md) — migraties compacter maken (baseline / archief) zonder alles met de hand in één gigantisch PHP-bestand te knutselen.
