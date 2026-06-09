# Migraties compacter maken (squash / baseline)

## Huidige aanpak

Het historische schema staat in **`app/Database/Pre2026Baseline.php`** (gegenereerd met `scripts/build-pre2026-baseline.php`). Bij `php artisan migrate` draait **één** migratie:

`database/migrations/2026_04_20_000001_install_nexa_application_schema.php`

Nieuwe schema-wijzigingen: voeg gewone migraties toe onder **`database/migrations/`** (niet `Pre2026Baseline.php` aanpassen tenzij je bewust een nieuwe baseline bouwt).

### Bestaande productie-database (upgrade)

Als de database al draaide vóór de squash (losse migraties in `migrations`), voert `2026_04_20_000001_install_nexa_application_schema` **geen** baseline-DDL opnieuw uit zodra de `users`-tabel bestaat. Laravel registreert alleen die ene migratie; daarna lopen de nieuwere migraties (`2026_04_20_000002_*` en later) normaal door.

Handmatig markeren (alleen nodig als `migrate` al gefaald is vóór deze fix):

```sql
INSERT INTO migrations (migration, batch)
SELECT '2026_04_20_000001_install_nexa_application_schema', COALESCE(MAX(batch), 0) + 1
FROM migrations
WHERE NOT EXISTS (
  SELECT 1 FROM migrations WHERE migration = '2026_04_20_000001_install_nexa_application_schema'
);
```

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
