# Migraties compacter maken (squash / baseline)

## Huidige aanpak

Het historische schema staat in **`app/Database/Pre2026Baseline.php`** (gegenereerd met `scripts/build-pre2026-baseline.php`). Bij `php artisan migrate` draait **één** migratie:

`database/migrations/2026_04_20_000001_install_nexa_application_schema.php`

Nieuwe schema-wijzigingen: voeg gewone migraties toe onder **`database/migrations/`** (niet `Pre2026Baseline.php` aanpassen tenzij je bewust een nieuwe baseline bouwt).

---

## Optioneel: opnieuw genereren vanuit losse bestanden

Als je weer een map met losse pre-baseline migraties hebt (bijv. uit git-historie), zet die onder `database/migrations_archive/pre-2026-baseline/` met dezelfde structuur als vroeger (`core/`, `shared/`, `modules/...`) en draai:

```bash
cd backend
php scripts/build-pre2026-baseline.php
```

---

## Optioneel: verder comprimeren naar één SQL-bestand

Zie `docs/DATABASE.md` — je kunt een `pg_dump --schema-only` maken en dat als alternatieve baseline gebruiken (aparte migratie en team-afspraak nodig).

## Wat we niet doen

- Automatisch honderden bestanden **blind** mergen zonder database-verificatie: te groot risico op stille schemaverschillen.
