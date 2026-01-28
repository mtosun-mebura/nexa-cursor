# Module Activatie Testen - Handleiding

## Hoe test je of module activatie werkt?

### Stap 1: Installeer de Module
1. Ga naar `/admin/modules`
2. Klik op "Installeer" bij de Skillmatching module
3. Status verandert naar "Geïnstalleerd"

### Stap 2: Activeer de Module
1. Klik op "Activeer" bij de Skillmatching module
2. Status verandert naar "Actief"

### Stap 3: Test de Module Routes

**Na activatie worden deze routes automatisch geregistreerd:**

#### Test Route (werkt ALLEEN als module actief is):
```
GET /admin/skillmatching/test
```
- **Als module NIET actief**: 404 Not Found
- **Als module WEL actief**: JSON response met module info

#### Module Routes (werkt ALLEEN als module actief is):
```
GET /admin/skillmatching/vacancies      → admin.skillmatching.vacancies.index
GET /admin/skillmatching/matches       → admin.skillmatching.matches.index  
GET /admin/skillmatching/interviews    → admin.skillmatching.interviews.index
```

### Stap 4: Controleer Routes

**Via Browser:**
- Ga naar: `http://localhost:8000/admin/skillmatching/test`
- Als module actief: Je ziet JSON met module info
- Als module niet actief: 404 error

**Via Terminal:**
```bash
php artisan route:list | grep skillmatching
```

Je zou moeten zien:
- `admin.skillmatching.test`
- `admin.skillmatching.vacancies.*`
- `admin.skillmatching.matches.*`
- `admin.skillmatching.interviews.*`

### Stap 5: Test Deactivatie

1. Klik op "Deactiveer" bij de module
2. Ga naar: `http://localhost:8000/admin/skillmatching/test`
3. Je krijgt nu een 404 error (route bestaat niet meer)
4. Routes zijn verwijderd uit het systeem

## Wat gebeurt er bij activatie?

1. ✅ Routes worden geregistreerd onder `/admin/{module-name}/`
2. ✅ Views worden geladen vanuit module directory
3. ✅ Permissions worden geregistreerd
4. ✅ Menu items kunnen worden toegevoegd (nog te implementeren)

## Huidige Status

### ✅ Werkt:
- Module installatie
- Module activatie/deactivatie
- Route registratie
- Test route

### ⚠️ Nog te doen:
- Menu items dynamisch toevoegen vanuit modules
- Oude routes conditioneel maken (alleen als module NIET actief)
- Views volledig migreren naar module directories

## Test Checklist

- [ ] Module installeren → Status wordt "Geïnstalleerd"
- [ ] Module activeren → Status wordt "Actief"
- [ ] Test route bezoeken → `/admin/skillmatching/test` werkt
- [ ] Routes checken → `php artisan route:list | grep skillmatching`
- [ ] Module deactiveren → Routes verdwijnen
- [ ] Test route opnieuw → 404 error
