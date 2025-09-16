# Vacatures Implementatie - Skillmatching AI Platform

## Overzicht

Deze implementatie bevat een volledig functioneel vacatures systeem met:
- Admin overzicht met tabel, sortering en status filtering
- Publieke vacatures pagina met SEO optimalisatie
- Automatische SEO metadata generatie
- Status management met kleurcodering
- Responsive design

## Functionaliteiten

### Admin Vacatures Overzicht

**Locatie:** `/admin/vacancies`

**Features:**
- Tabel overzicht van alle vacatures
- Status statistieken (Open, Gesloten, In behandeling)
- Sortering op alle kolommen (nieuwste eerst standaard)
- Filtering op status, categorie, bedrijf
- Status kleurcodering:
  - **Open**: Licht groen (#90EE90)
  - **Gesloten**: Licht rood (#FFB6C1) 
  - **In behandeling**: Licht oranje (#FFD700)
- SEO indicator per vacature
- Snelle acties (openen/sluiten, bewerken, verwijderen)

### Publieke Vacatures Pagina

**Locatie:** `/vacatures`

**Features:**
- Moderne, responsive design
- Zoekfunctie met filters (locatie, categorie, werktype, remote)
- SEO geoptimaliseerd met:
  - Meta tags (title, description, keywords)
  - Open Graph tags
  - Twitter Card tags
  - Structured data (Schema.org)
  - Canonical URLs
- Paginering
- Gerelateerde vacatures

### Vacature Detail Pagina

**Locatie:** `/vacatures/{company-slug}/{vacancy-id}`

**Features:**
- Volledige vacature informatie
- Bedrijfsinformatie
- Gerelateerde vacatures
- SEO geoptimaliseerd
- Breadcrumb navigatie
- Call-to-action knoppen

## SEO Implementatie

### Automatische SEO Generatie

Elke vacature krijgt automatisch SEO metadata:

1. **Meta Title**: Vacature titel + bedrijfsnaam
2. **Meta Description**: Geoptimaliseerde beschrijving (max 160 karakters)
3. **Meta Keywords**: Automatisch gegenereerde keywords
4. **Structured Data**: Schema.org JobPosting markup

### SEO Score Indicator

In het admin panel wordt een SEO score getoond:
- **Groen**: 3-4 SEO elementen aanwezig
- **Oranje**: 2 SEO elementen aanwezig  
- **Rood**: 0-1 SEO elementen aanwezig

### Structured Data

Elke vacature bevat gestructureerde data voor zoekmachines:

```json
{
  "@context": "https://schema.org",
  "@type": "JobPosting",
  "title": "Vacature titel",
  "description": "Vacature beschrijving",
  "datePosted": "2024-01-01T00:00:00Z",
  "validThrough": "2024-02-01T00:00:00Z",
  "employmentType": "Fulltime",
  "jobLocation": {
    "@type": "Place",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Amsterdam"
    }
  },
  "hiringOrganization": {
    "@type": "Organization",
    "name": "Bedrijfsnaam"
  }
}
```

## Database Structuur

### Vacancy Model

```php
protected $fillable = [
    'company_id', 'title', 'location', 'employment_type', 
    'description', 'requirements', 'offer', 'application_instructions',
    'category_id', 'reference_number', 'logo', 'salary_range',
    'start_date', 'working_hours', 'travel_expenses', 'remote_work',
    'status', 'language', 'publication_date', 'closing_date',
    'meta_title', 'meta_description', 'meta_keywords'
];
```

### Status Waarden

- `Open`: Actieve vacature
- `Gesloten`: Gesloten vacature
- `In behandeling`: Vacature in review

## Routes

### Admin Routes
- `GET /admin/vacancies` - Vacatures overzicht
- `GET /admin/vacancies/create` - Nieuwe vacature
- `POST /admin/vacancies` - Vacature opslaan
- `GET /admin/vacancies/{id}/edit` - Vacature bewerken
- `PUT /admin/vacancies/{id}` - Vacature updaten
- `DELETE /admin/vacancies/{id}` - Vacature verwijderen

### Publieke Routes
- `GET /vacatures` - Publiek vacatures overzicht
- `GET /vacatures/{company-slug}/{vacancy-id}` - Vacature detail

## Installatie & Setup

### 1. Database Migrations
```bash
php artisan migrate
```

### 2. Seeders Uitvoeren
```bash
php artisan db:seed
```

Dit maakt aan:
- Rollen en gebruikers
- Categorieën (40 verschillende job categorieën)
- Test vacatures (8 verschillende vacatures)

### 3. Test Data
De seeder maakt 8 test vacatures aan met verschillende:
- Statussen (Open, Gesloten, In behandeling)
- Locaties (verschillende Nederlandse steden)
- Werktypes (Fulltime, Parttime, Contract)
- Categorieën (willekeurig toegewezen)

## Gebruik

### Admin Panel

1. **Inloggen**: Ga naar `/admin/login`
2. **Vacatures bekijken**: Ga naar `/admin/vacancies`
3. **Filteren**: Gebruik de filter opties bovenaan
4. **Sorteren**: Klik op kolomkoppen
5. **Status wijzigen**: Gebruik de actie knoppen

### Publieke Pagina

1. **Vacatures bekijken**: Ga naar `/vacatures`
2. **Zoeken**: Gebruik de zoekfilters
3. **Detail bekijken**: Klik op een vacature
4. **Solliciteren**: Gebruik de "Direct Solliciteren" knop

## Technische Details

### Controllers

- `AdminVacancyController`: Admin functionaliteit
- `PublicVacancyController`: Publieke functionaliteit
- `VacancyController`: API endpoints

### Views

- `admin/vacancies/index.blade.php`: Admin overzicht
- `public/vacancies/index.blade.php`: Publiek overzicht
- `public/vacancies/show.blade.php`: Vacature detail

### Models

- `Vacancy`: Hoofdmodel met relaties en SEO methoden
- `Company`: Bedrijfsrelatie
- `Category`: Categorie relatie

## SEO Best Practices

### Implementeerde SEO Features

1. **Meta Tags**: Volledige set meta tags per pagina
2. **Structured Data**: Schema.org markup voor job postings
3. **Canonical URLs**: Voorkomt duplicate content
4. **Open Graph**: Social media sharing optimalisatie
5. **Twitter Cards**: Twitter sharing optimalisatie
6. **Breadcrumbs**: Navigatie en SEO
7. **Semantic HTML**: Goede HTML structuur
8. **Mobile Responsive**: Google mobile-first indexing

### SEO Score Berekening

```php
$seoScore = 0;
if ($vacancy->meta_title) $seoScore++;
if ($vacancy->meta_description) $seoScore++;
if ($vacancy->meta_keywords) $seoScore++;
if ($vacancy->description && strlen($vacancy->description) > 100) $seoScore++;
```

## Customization

### Status Kleuren Aanpassen

In `Vacancy` model, `getStatusColorAttribute()` methode:

```php
public function getStatusColorAttribute()
{
    return match($this->status) {
        'Open' => '#90EE90', // licht groen
        'Gesloten' => '#FFB6C1', // licht rood
        'In behandeling' => '#FFD700', // licht oranje
        default => '#E0E0E0', // licht grijs
    };
}
```

### SEO Keywords Aanpassen

In `Vacancy` model, `generateMetaKeywords()` methode:

```php
private function generateMetaKeywords($vacancy)
{
    $keywords = ['vacature', 'werk', 'baan', 'sollicitatie'];
    // Voeg custom keywords toe
    return implode(', ', array_unique($keywords));
}
```

## Troubleshooting

### Veelvoorkomende Problemen

1. **Geen vacatures zichtbaar**: Controleer of seeders zijn uitgevoerd
2. **SEO velden leeg**: Controleer of vacatures correct zijn aangemaakt
3. **Status kleuren niet zichtbaar**: Controleer CSS en Font Awesome
4. **Paginering werkt niet**: Controleer Bootstrap CSS/JS

### Debug Tips

1. **Database checken**: `php artisan tinker` -> `Vacancy::count()`
2. **Routes checken**: `php artisan route:list`
3. **Cache legen**: `php artisan cache:clear`

## Toekomstige Uitbreidingen

### Mogelijke Features

1. **AI Matching**: Automatische kandidaat-vacature matching
2. **Email Notifications**: Vacature alerts
3. **Advanced Search**: Elasticsearch integratie
4. **Analytics**: Vacature views en clicks tracking
5. **Multi-language**: Meertalige vacatures
6. **API Endpoints**: REST API voor externe integraties

### Performance Optimalisatie

1. **Caching**: Redis cache voor veel bezochte pagina's
2. **Database Indexing**: Indexes op veel gezochte velden
3. **CDN**: Content Delivery Network voor assets
4. **Image Optimization**: Geoptimaliseerde afbeeldingen

## Support

Voor vragen of problemen:
1. Controleer de Laravel logs: `storage/logs/laravel.log`
2. Controleer de database connectie
3. Controleer de file permissions
4. Controleer de .env configuratie

