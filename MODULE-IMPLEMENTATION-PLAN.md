# Module Systeem Implementatie Plan

## Quick Start - Eerste Stappen

### Stap 1: Basis Structuur Aanmaken

```bash
# Create directories
mkdir -p backend/app/Modules/Base
mkdir -p backend/app/Modules/Skillmatching/{Controllers,Models,Routes,Views,Assets,Migrations}
mkdir -p backend/app/Services
mkdir -p backend/modules
mkdir -p backend/database/migrations/core
mkdir -p backend/database/migrations/modules/skillmatching
```

### Stap 2: Core Files Aanmaken

1. **Module Base Class** → `backend/app/Modules/Base/Module.php`
2. **ModuleManager Service** → `backend/app/Services/ModuleManager.php`
3. **Module Model** → `backend/app/Models/Module.php`
4. **Module Migrations** → `backend/database/migrations/core/create_modules_table.php`
5. **Module Controller** → `backend/app/Http/Controllers/Admin/AdminModuleController.php`

### Stap 3: Bestaande Code Migreren

**Vacancies → Skillmatching Module:**
- Move `AdminVacancyController` → `app/Modules/Skillmatching/Controllers/VacancyController.php`
- Move `Vacancy` model → `app/Modules/Skillmatching/Models/Vacancy.php`
- Move views → `app/Modules/Skillmatching/Views/vacancies/`
- Move routes → `app/Modules/Skillmatching/Routes/web.php`

**Matches → Skillmatching Module:**
- Move `AdminMatchController` → `app/Modules/Skillmatching/Controllers/MatchController.php`
- Move `Match` model → `app/Modules/Skillmatching/Models/Match.php`

**Interviews → Skillmatching Module:**
- Move `AdminInterviewController` → `app/Modules/Skillmatching/Controllers/InterviewController.php`
- Move `Interview` model → `app/Modules/Skillmatching/Models/Interview.php`

### Stap 4: Routes Aanpassen

**Huidige routes:**
```php
Route::resource('vacancies', AdminVacancyController::class);
Route::resource('matches', AdminMatchController::class);
Route::resource('interviews', AdminInterviewController::class);
```

**Nieuwe module routes:**
```php
// In app/Modules/Skillmatching/Routes/web.php
Route::prefix('skillmatching')->group(function() {
    Route::resource('vacancies', VacancyController::class);
    Route::resource('matches', MatchController::class);
    Route::resource('interviews', InterviewController::class);
});
```

### Stap 5: Menu Aanpassen

Menu items worden nu dynamisch geregistreerd via de module:
```php
// In SkillmatchingModule::registerMenuItems()
return [
    ['title' => 'Vacatures', 'route' => 'admin.skillmatching.vacancies.index', ...],
    ['title' => 'Matches', 'route' => 'admin.skillmatching.matches.index', ...],
    ['title' => 'Interviews', 'route' => 'admin.skillmatching.interviews.index', ...],
];
```

---

## Prioriteit Volgorde

### 🔴 Hoge Prioriteit (Week 1)
1. Base Module class & interface
2. ModuleManager service
3. Database schema (modules table)
4. Module discovery systeem
5. Admin module management pagina

### 🟡 Medium Prioriteit (Week 2)
1. Migrate Vacancies naar module
2. Migrate Matches naar module
3. Migrate Interviews naar module
4. Update routes & menu
5. Test module installatie/activatie

### 🟢 Lage Prioriteit (Week 3+)
1. Frontend website builder
2. Theme systeem
3. Page builder UI
4. Taxi module voorbeeld
5. Documentatie

---

## Migratie Checklist

### Voor elke resource (Vacancy, Match, Interview):

- [ ] Controller verplaatsen naar module
- [ ] Model verplaatsen naar module
- [ ] Views verplaatsen naar module
- [ ] Routes updaten
- [ ] Menu items updaten
- [ ] Permissions updaten
- [ ] Migrations verplaatsen
- [ ] Tests updaten
- [ ] Documentatie updaten

---

## Testing Checklist

- [ ] Module discovery werkt
- [ ] Module installatie werkt
- [ ] Module activatie werkt
- [ ] Module deactivatie werkt
- [ ] Routes worden correct geregistreerd
- [ ] Menu items verschijnen
- [ ] Permissions werken
- [ ] Views worden geladen
- [ ] Assets worden geladen
- [ ] Migrations draaien correct

---

## Rollback Plan

Als er problemen zijn:
1. Keep old controllers/models in place initially
2. Use feature flags to switch between old/new
3. Gradual migration per resource
4. Test thoroughly before removing old code
