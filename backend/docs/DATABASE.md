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

**Modules** (Skillmatching, Nexa Taxi) zijn **geen aparte Laravel-apps**; ze gebruiken **dezelfde** `backend/.env` en `config/database.php`. Alleen bij `MODULE_USE_SINGLE_DATABASE=false` kunnen er **extra database-connections** (`module_*`) worden geregistreerd — nog steeds geconfigureerd via dezelfde `.env` (`DB_*` + module-instellingen).

---

## Eén database / één schema

In **`backend/.env`**:

```env
MODULE_USE_SINGLE_DATABASE=true
```

- Alle tabellen (kern + module) horen in **dezelfde** database als `DB_DATABASE`.
- Er worden **geen** aparte `module_taxi`-connections voor dit patroon gebruikt (`ModuleDatabaseService::supportsModuleDatabases()` wordt dan `false`).

Zet je `MODULE_USE_SINGLE_DATABASE=false` (default), dan kan de app bij MySQL/PostgreSQL **per module een eigen database** gebruiken; de **hoofd-DB** (`DB_*`) blijft voor o.a. `users`, `modules`, `general_settings`.

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

Bij **`MODULE_USE_SINGLE_DATABASE=true`** is één `migrate` op die database voldoende.

---

## Zie ook

- [MIGRATION_SQUASH.md](./MIGRATION_SQUASH.md) — migraties compacter maken (baseline / archief) zonder alles met de hand in één gigantisch PHP-bestand te knutselen.
