# Nexa database-datamodel

Dit document beschrijft het logische datamodel van:

- **Nexa**: platform, tenants, gebruikers, modules, content, communicatie en facturatie.
- **Nexa Taxi**: voertuigen, ritten, dispatch, betalingen en de AI-kennisbank.
- **Nexa Skillmatching**: vacatures, kandidaten, matches, sollicitaties en recruitment-pipelines.

Bronnen: `app/Database/Pre2026Baseline.php`, `database/migrations/` en
`database/migrations/modules/taxi/`. Het model beschrijft de toestand na alle
migraties tot en met **8 juni 2026**.

## Fysieke indeling

De standaardstrategie is `MODULE_DATABASE_STRATEGY=schema`:

| PostgreSQL-schema | Verantwoordelijkheid |
|---|---|
| `public` | Nexa core en gedeelde platformtabellen |
| `nexa_taxi` | Nexa Taxi |
| `nexa_skillmatching` | Nexa Skillmatching |

Bij de legacy-strategie `database` kunnen modules in aparte databases staan. Daarom
zijn enkele relaties vanuit modules naar core, zoals `company_id`, `driver_id`,
`customer_user_id` en `invoice_id`, bewust alleen logische relaties zonder
database-foreign-key.

Legenda:

- `PK`: primary key
- `FK`: afgedwongen foreign key
- `REF`: logische verwijzing, niet altijd afgedwongen door de database
- `UK`: unique key

## 1. Nexa core

```mermaid
erDiagram
    COMPANIES {
        bigint id PK
        string name
        string slug UK
        string email
        string city
        boolean is_active
        bigint frontend_theme_id FK
    }

    USERS {
        bigint id PK
        bigint company_id FK
        string email UK
        string first_name
        string last_name
        string password
        boolean is_active
    }

    COMPANY_LOCATIONS {
        bigint id PK
        bigint company_id FK
        string name
        string city
        decimal latitude
        decimal longitude
        boolean is_main
    }

    COMPANY_DOMAINS {
        bigint id PK
        bigint company_id FK
        string host UK
        boolean is_primary
    }

    MODULES {
        bigint id PK
        string name UK
        string display_name
        string version
        boolean installed
        boolean active
        json configuration
    }

    COMPANY_MODULE {
        bigint id PK
        bigint company_id FK
        bigint module_id FK
        json settings
    }

    FRONTEND_THEMES {
        bigint id PK
        string slug UK
        string name
        boolean is_active
        json settings
        json default_blocks
    }

    WEBSITE_PAGES {
        bigint id PK
        bigint company_id FK
        bigint frontend_theme_id FK
        string slug
        string title
        string page_type
        string module_name
        longtext content
        boolean is_active
    }

    GENERAL_SETTINGS {
        bigint id PK
        bigint company_id FK
        string key
        text value
    }

    EMAIL_TEMPLATES {
        bigint id PK
        bigint company_id FK
        string name
        string subject
        text body
        bigint recipient_user_id FK
    }

    NOTIFICATIONS {
        bigint id PK
        bigint user_id FK
        bigint company_id FK
        bigint email_template_id FK
        bigint original_notification_id FK
        text content
        boolean is_read
        string category
    }

    INVOICES {
        bigint id PK
        bigint company_id FK
        string invoice_number UK
        string module
        bigint module_reference_id "REF: module record"
        decimal total_amount
        string status
        date due_date
    }

    PAYMENTS {
        bigint id PK
        bigint company_id FK
        bigint invoice_id FK
        decimal amount
        string status
        string payment_provider
    }

    PAYMENT_REMINDERS {
        bigint id PK
        bigint invoice_id FK
        bigint company_id FK
        string reminder_type
        timestamp sent_at
    }

    PAYMENT_PROVIDERS {
        bigint id PK
        bigint company_id FK
        string name
        string provider_type
        boolean is_active
        json config
    }

    INVOICE_SETTINGS {
        bigint id PK
        bigint company_id "REF: companies.id"
        bigint location_id "REF: company_locations.id"
        string invoice_number_prefix
        decimal default_tax_rate
        integer payment_terms_days
    }

    CHATS {
        bigint id PK
        bigint user_id FK
        bigint company_id FK
        boolean is_active
        timestamp ended_at
    }

    CHAT_MESSAGES {
        bigint id PK
        bigint chat_id FK
        bigint sender_id
        string sender_type
        text message
    }

    AI_CHAT_AUDIT_LOGS {
        bigint id PK
        bigint company_id "REF: companies.id"
        bigint user_id "REF: users.id"
        string channel
        string intent
        boolean allow_live_data
        string data_source
    }

    COMPANIES ||--o{ USERS : employs
    COMPANIES ||--o{ COMPANY_LOCATIONS : has
    COMPANIES ||--o{ COMPANY_DOMAINS : uses
    COMPANIES ||--o{ COMPANY_MODULE : enables
    MODULES ||--o{ COMPANY_MODULE : assigned
    FRONTEND_THEMES ||--o{ COMPANIES : selected_by
    COMPANIES ||--o{ WEBSITE_PAGES : owns
    FRONTEND_THEMES ||--o{ WEBSITE_PAGES : renders
    COMPANIES ||--o{ GENERAL_SETTINGS : configures
    COMPANIES ||--o{ EMAIL_TEMPLATES : owns
    USERS ||--o{ NOTIFICATIONS : receives
    EMAIL_TEMPLATES ||--o{ NOTIFICATIONS : generates
    NOTIFICATIONS ||--o{ NOTIFICATIONS : groups
    COMPANIES ||--o{ INVOICES : billed
    INVOICES ||--o{ PAYMENTS : paid_by
    INVOICES ||--o{ PAYMENT_REMINDERS : triggers
    COMPANIES ||--o{ PAYMENT_PROVIDERS : configures
    COMPANIES ||--o{ CHATS : owns
    USERS ||--o{ CHATS : participates
    CHATS ||--o{ CHAT_MESSAGES : contains
    COMPANIES ||--o{ AI_CHAT_AUDIT_LOGS : audits
    USERS ||--o{ AI_CHAT_AUDIT_LOGS : initiates
```

### Core-hulptabellen

| Groep | Tabellen |
|---|---|
| Authenticatie | `password_reset_tokens`, `customer_login_codes`, `personal_access_tokens`, `account_activation_tokens` |
| Autorisatie | `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` |
| Laravel runtime | `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` |
| Website/media | `website_media`, `info_request_form_fields` |
| Chat legacy/realtime | `chat_rooms`, `chat_participants`, `typing_indicators`, `chat_history` |

## 2. Nexa Taxi

```mermaid
erDiagram
    CORE_COMPANIES {
        bigint id PK
        string name
    }

    CORE_USERS {
        bigint id PK
        string email
    }

    CORE_INVOICES {
        bigint id PK
        string invoice_number
    }

    VEHICLES {
        bigint id PK
        bigint company_id "REF: core companies.id"
        string name
        string type
        string license_plate
        smallint seats
        string person_range
        decimal base_fare
        decimal price_per_km
        decimal price_per_min
        boolean active
    }

    DEFAULT_RATES {
        bigint id PK
        string person_range
        decimal base_fare
        decimal min_fare
        decimal price_per_km
        decimal price_per_min
        decimal cleaning_costs
    }

    RIDE_REQUESTS {
        bigint id PK
        bigint company_id "REF: core companies.id"
        bigint vehicle_id FK
        bigint driver_id "REF: core users.id"
        bigint customer_user_id "REF: core users.id"
        bigint invoice_id "REF: core invoices.id"
        string status
        string pickup_address
        string dropoff_address
        datetime pickup_at
        integer distance_meters
        integer duration_seconds
        decimal quoted_price
        decimal final_price
        string payment_method
        string payment_status
    }

    DRIVER_AVAILABILITY {
        bigint driver_id PK
        bigint company_id "REF: core companies.id"
        boolean is_online
        decimal lat
        decimal lng
        timestamp location_updated_at
        timestamp last_seen_at
    }

    RIDE_DISPATCH_OFFERS {
        bigint id PK
        bigint ride_request_id "REF: ride_requests.id"
        bigint company_id "REF: core companies.id"
        bigint driver_id "REF: core users.id"
        string status
        smallint wave
        timestamp offered_at
        timestamp expires_at
        timestamp responded_at
    }

    RIDE_PAYMENTS {
        bigint id PK
        bigint ride_request_id "REF: ride_requests.id"
        bigint company_id "REF: core companies.id"
        string channel
        string mollie_payment_id UK
        decimal amount
        string currency
        string status
        timestamp paid_at
    }

    RIDE_REQUEST_NOTIFICATION_LOGS {
        bigint id PK
        bigint ride_request_id "REF: ride_requests.id"
        bigint driver_id "REF: core users.id"
        string channel
        string status
        string recipient_address
        json meta
    }

    KNOWLEDGE_DOCUMENTS {
        bigint id PK
        text title
        text content
        text category
        vector embedding
    }

    KNOWLEDGE_CHUNKS {
        bigint id PK
        bigint document_id FK
        text chunk_text
        vector embedding
    }

    CORE_COMPANIES ||--o{ VEHICLES : owns
    CORE_COMPANIES ||--o{ RIDE_REQUESTS : receives
    VEHICLES ||--o{ RIDE_REQUESTS : selected_for
    CORE_USERS ||--o{ RIDE_REQUESTS : drives
    CORE_USERS ||--o{ RIDE_REQUESTS : books
    CORE_INVOICES ||--o| RIDE_REQUESTS : invoices
    CORE_USERS ||--o| DRIVER_AVAILABILITY : reports
    RIDE_REQUESTS ||--o{ RIDE_DISPATCH_OFFERS : dispatches
    CORE_USERS ||--o{ RIDE_DISPATCH_OFFERS : receives
    RIDE_REQUESTS ||--o{ RIDE_PAYMENTS : has
    RIDE_REQUESTS ||--o{ RIDE_REQUEST_NOTIFICATION_LOGS : logs
    KNOWLEDGE_DOCUMENTS ||--o{ KNOWLEDGE_CHUNKS : split_into
```

Belangrijke constraints:

- `ride_dispatch_offers` is uniek op `(ride_request_id, driver_id)`.
- `driver_availability` heeft precies één actuele rij per chauffeur.
- `knowledge_documents.embedding` en `knowledge_chunks.embedding` gebruiken
  `vector(1536)` wanneer `pgvector` beschikbaar is, anders tekst.
- `invoices.module = 'taxi'` en `invoices.module_reference_id = ride_requests.id`
  vormen de generieke core-koppeling naar een taxirit.

## 3. Nexa Skillmatching

```mermaid
erDiagram
    COMPANIES {
        bigint id PK
        string name
    }

    USERS {
        bigint id PK
        bigint company_id FK
        bigint job_title_id FK
        string email UK
        string function
    }

    BRANCHES {
        bigint id PK
        string name
        string slug UK
    }

    BRANCH_FUNCTIONS {
        bigint id PK
        bigint branch_id FK
        string name
    }

    BRANCH_FUNCTION_SKILLS {
        bigint id PK
        bigint branch_function_id FK
        string name
    }

    JOB_TITLES {
        bigint id PK
        string name UK
        integer usage_count
    }

    VACANCIES {
        bigint id PK
        bigint company_id FK
        bigint branch_id FK
        bigint contact_user_id FK
        string title
        string location
        string employment_type
        string status
        json required_skills
        json nice_to_have
        json tools_tech
        string work_mode
        integer min_experience
    }

    CANDIDATES {
        bigint id PK
        string reference_number UK
        string email UK
        string first_name
        string last_name
        string status
        json skills
        json languages
        json primary_titles
        json sectors
        integer experience_years
        boolean consent_gdpr
    }

    MATCHES {
        bigint id PK
        bigint candidate_id FK
        bigint vacancy_id FK
        decimal match_score
        string status
        text ai_feedback
    }

    APPLICATIONS {
        bigint id PK
        bigint candidate_id FK
        bigint vacancy_id FK
        bigint user_id FK
        string status
        timestamp created_at
    }

    INTERVIEWS {
        bigint id PK
        bigint match_id FK
        bigint company_id FK
        bigint company_location_id FK
        bigint interviewer_user_id FK
        timestamp scheduled_at
        string type
        string status
        string location
        text feedback
    }

    PIPELINE_TEMPLATES {
        bigint id PK
        bigint company_id FK
        string name
        string key
        integer version
        json stages
        boolean is_default
    }

    STAGE_TYPES {
        bigint id PK
        string key UK
        string default_label
        string category
        json outcomes
        json allowed_next_stage_types
    }

    STAGE_INSTANCES {
        bigint id PK
        bigint application_id FK
        bigint match_id FK
        bigint pipeline_template_id FK
        string stage_type_key "REF: stage_types.key"
        string label
        integer sequence
        string status
        string outcome
        timestamp scheduled_at
    }

    CANDIDATE_ACTIVITIES {
        bigint id PK
        bigint candidate_id FK
        bigint vacancy_id FK
        bigint match_id FK
        bigint application_id FK
        bigint interview_id FK
        bigint user_id FK
        string action
        timestamp action_at
        json metadata
    }

    CANDIDATE_TEXTS {
        bigint candidate_id PK
        text last_responsibilities
        json top_skills
        json tools_tech
        text employer_values
    }

    CANDIDATE_EMBEDDINGS {
        bigint candidate_id PK
        string model
        vector embedding
    }

    VACANCY_EMBEDDINGS {
        bigint vacancy_id PK
        string model
        vector embedding
    }

    FAVORITES {
        bigint id PK
        bigint user_id FK
        bigint vacancy_id FK
    }

    COMPANIES ||--o{ USERS : employs
    JOB_TITLES ||--o{ USERS : classifies
    BRANCHES ||--o{ BRANCH_FUNCTIONS : contains
    BRANCH_FUNCTIONS ||--o{ BRANCH_FUNCTION_SKILLS : requires
    COMPANIES ||--o{ VACANCIES : publishes
    BRANCHES ||--o{ VACANCIES : categorizes
    USERS ||--o{ VACANCIES : contact_for
    CANDIDATES ||--o{ MATCHES : receives
    VACANCIES ||--o{ MATCHES : produces
    CANDIDATES ||--o{ APPLICATIONS : submits
    VACANCIES ||--o{ APPLICATIONS : receives
    USERS ||--o{ APPLICATIONS : account_for
    MATCHES ||--o{ INTERVIEWS : schedules
    COMPANIES ||--o{ INTERVIEWS : hosts
    COMPANIES ||--o{ PIPELINE_TEMPLATES : defines
    PIPELINE_TEMPLATES ||--o{ STAGE_INSTANCES : instantiates
    APPLICATIONS ||--o{ STAGE_INSTANCES : progresses
    MATCHES ||--o{ STAGE_INSTANCES : progresses
    STAGE_TYPES ||--o{ STAGE_INSTANCES : classifies
    CANDIDATES ||--o{ CANDIDATE_ACTIVITIES : has
    VACANCIES ||--o{ CANDIDATE_ACTIVITIES : concerns
    CANDIDATES ||--o| CANDIDATE_TEXTS : enriches
    CANDIDATES ||--o| CANDIDATE_EMBEDDINGS : vectorizes
    VACANCIES ||--o| VACANCY_EMBEDDINGS : vectorizes
    USERS ||--o{ FAVORITES : marks
    VACANCIES ||--o{ FAVORITES : saved_as
```

### Overige Skillmatching-tabellen

| Tabel | Doel |
|---|---|
| `job_configuration_types` | Definieert typen zoals dienstverband, werkuren en vacaturestatus |
| `job_configurations` | Globale of tenant-specifieke waarden per configuratietype |
| `skills` | Vaardigheden op een gebruikersprofiel |
| `experiences` | Werkervaring op een gebruikersprofiel |
| `cv_files` | CV-bestanden van gebruikers |
| `chats` / `chat_messages` | Gesprekken gekoppeld aan kandidaat, match of sollicitatie |

Belangrijke constraints:

- `favorites` is uniek op `(user_id, vacancy_id)`.
- `branch_functions` is uniek op `(branch_id, name)`.
- `branch_function_skills` is uniek op `(branch_function_id, name)`.
- `candidate_texts`, `candidate_embeddings` en `vacancy_embeddings` zijn
  één-op-één uitbreidingen via hun primary key.
- `stage_instances.stage_type_key` verwijst logisch naar `stage_types.key`; er is
  geen database-foreign-key.
- Embeddings gebruiken `vector(1536)` op PostgreSQL met `pgvector`, met JSON als
  fallback in omgevingen zonder de extensie.

## Hoofdprocessen

### Taxi

`company -> vehicle -> ride_request -> dispatch_offer -> driver -> ride_payment/invoice`

### Skillmatching

`company -> vacancy -> candidate match/application -> pipeline stages -> interview/outcome`

### Multi-tenancy

`company` is de tenant-root. Core-data gebruikt waar mogelijk een echte foreign
key naar `companies.id`. Moduledata gebruikt daarnaast `company_id` als
tenant-sleutel, maar die verwijzing is niet overal fysiek afgedwongen vanwege de
ondersteuning voor zowel PostgreSQL-schema's als losse module-databases.
