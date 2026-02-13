# Module-schema's en thema's

## Database per module (PostgreSQL)

Bij **installatie van een module** (alleen wanneer `DB_CONNECTION=pgsql`):

1. Er wordt een **apart schema** aangemaakt: `module_{naam}` (bijv. `module_skillmatching`).
2. In dat schema worden **standaardtabellen** aangemaakt: `users`, `sessions`, `password_reset_tokens`, `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`, `cache`, `cache_locks`, `jobs`.
3. Er wordt een **superadmin** aangemaakt in dat schema:
   - E-mail: **m.tosun@mebura.nl**
   - Wachtwoord: **!**
   - Rol: super-admin (voor configuraties).
4. Als de module een **Migrations**-map heeft (`app/Modules/{Name}/Migrations/`), worden die migraties binnen het module-schema uitgevoerd.

Bij **deïnstallatie** van een module wordt het schema (CASCADE) verwijderd.

- Service: `App\Services\ModuleSchemaService`
- Config: geen; alleen actief bij PostgreSQL.

## Thema's uit `backend/themas/`

De map **themas** staat binnen de Laravel-app in **`backend/themas/`** (buiten `public/`), zodat bronbestanden nooit direct via de webserver bereikbaar zijn.

- **atom-v2** – statische HTML/CSS/JS
- **nextly-template-main** – Next.js (React)
- **next-landing-vpn-main** – Next.js (React)

Bij **module-installatie**:

1. Alle thema's worden gekopieerd naar **`public/frontend-themes/`** (atom-v2, nextly-template, next-landing-vpn).
2. Dezelfde thema's worden gekopieerd naar **`app/Modules/{Name}/Resources/frontend/themes/`**, zodat het frontend van de module bij activatie direct beschikbaar is.

**Handmatig synchroniseren** (zonder module opnieuw te installeren):

```bash
php artisan themas:sync              # Alleen naar public/frontend-themes/
php artisan themas:sync --modules    # Ook naar alle geïnstalleerde modules
```

**Config** (optioneel in `.env`):

- `THEMAS_SOURCE_PATH` – absoluut pad naar de themas-map (standaard: `base_path('themas')` = `backend/themas/`).

- Service: `App\Services\ThemeCopyService`
- Config: `config('app.themas_source_path')`

## Frontend-thema's in de applicatie

De bestaande **FrontendTheme**-records (modern, classic, minimal) en de **thema’s per module** in de admin (Frontend Thema's → Thema per module) bepalen welk uiterlijk gebruikt wordt. De gekopieerde bestanden in `public/frontend-themes/` en in de module-mappen zijn beschikbaar voor assets en eventuele Blade-layouts; de koppeling met een specifiek FrontendTheme (slug → map) kan later in views worden toegevoegd (bijv. `atom-v2` → classic).
