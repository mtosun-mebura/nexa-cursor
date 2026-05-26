# Tests – Nexa Backend

Unit- en featuretests voor de applicatie. Bedoeld om bij **deploys** te controleren of er niets is omgevallen.

## Testen draaien

In de **backend**-map:

```bash
cd backend
php artisan test
```

Alleen Unit-tests:

```bash
php artisan test --testsuite=Unit
```

Alleen Feature-tests:

```bash
php artisan test --testsuite=Feature
```

Specifieke testclass of methode:

```bash
php artisan test tests/Feature/AdminRoutesSmokeTest.php
php artisan test --filter=admin_dashboard_redirects_guest_to_login
```

## Omgeving

- **PHPUnit** met Laravel TestCase
- **Database:** sqlite in-memory (`DB_DATABASE=:memory:`)
- **RefreshDatabase** in de base TestCase

Migraties komen uit **`database/migrations/`** (bundel `2026_04_20_000001_install_nexa_application_schema.php`), die op SQLite alleen **core + shared** uit het archief uitvoert — geen module-migraties (PostgreSQL-specifieke SQL). Daarmee ontstaan o.a. `users`, `general_settings`, `frontend_themes`, `website_pages`. De test **admin dashboard returns 200** wordt overgeslagen als de tabel `vacancies` ontbreekt (die zit in module-migraties).

## Wat wordt getest

### Unit

- **WebsitePageModelTest** – Model WebsitePage: `scopeActive`, `scopeShowInMenu`, fillable, casts. Slaat over als `frontend_themes` ontbreekt.
- **GoogleReviewsServiceTest** – GoogleReviewsService: lege structuur bij geen config, `normalizePlaceId`, `looksLikePlaceId`. Slaat over als `general_settings` ontbreekt.
- **BranchModelTest** – Branch-model (bestaand).
- **FormValidationUnitTest** – Form-sanitize (bestaand).

### Feature

- **AdminRoutesSmokeTest** – Guest wordt doorgestuurd (login of meld/sessie-verlopen); dashboard geeft 200 voor super-admin.
- **GuestRoutesSmokeTest** – Home- en admin-loginpagina geven een geldige response.
- **WebsitePageCrudAndPreviewTest** – Website-pagina’s: index vereist auth, create-form laadt, preview 200, store met `show_in_menu`.
- **WebsitePageUpdateTest** – Update home-sections en componenten (bestaand).
- **AdminLoginTest** – Admin-login (bestaand).

### Overige

- **RoleManagementTest**, **BranchTest**, **FormValidationTest**, **DropdownValidationTest**, **CardsRondeHoekenViewTest** – bestaande featuretests.

## In CI/CD (deploy)

Voer vóór of na deploy uit:

```bash
cd backend
composer install --no-dev  # of met --dev als je tests op de deploy-runner draait
php artisan test
```

Bij een niet-nul exit code de deploy laten falen. Zo controleer je of de belangrijkste flows en modellen nog werken.

## Nieuwe tests toevoegen

1. **Unit:** onder `tests/Unit/`, extend `Tests\TestCase`, gebruik `#[Test]` (PHPUnit\Framework\Attributes\Test) of `test_`-prefix voor methoden.
2. **Feature:** onder `tests/Feature/`, voor HTTP en auth; gebruik `$this->actingAs($user)` voor ingelogde gebruikers en `Role::firstOrCreate` + `assignRole('super-admin')` waar nodig.
3. Bij afhankelijkheid van tabellen (bijv. `frontend_themes`, `general_settings`): `Schema::hasTable()` gebruiken en anders `$this->markTestSkipped('...')` om te voorkomen dat de suite crasht als migraties niet (volledig) zijn gedraaid.
