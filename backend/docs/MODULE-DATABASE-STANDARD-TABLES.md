# Standaard- en module-specifieke tabellen bij een nieuw module-schema

Bij het aanmaken van een nieuwe module-database (bijv. `nexa_taxiroyaal`) worden momenteel **alle** migraties uit `database/migrations` gedraaid. Hieronder staat welke tabellen als **standaard** (voor elke module) worden gezien en welke bij een **bepaalde module** horen.

---

## Standaard tabellen (voor elk nieuw module-schema)

Deze tabellen horen bij de basisplatform en worden in elke module-database aangemaakt.

### Laravel basis
| Tabel | Beschrijving |
|-------|--------------|
| `users` | Gebruikers |
| `password_reset_tokens` | Wachtwoord-reset tokens |
| `sessions` | Sessies |
| `cache` | Cache |
| `cache_locks` | Cache locks |
| `jobs` | Queue jobs |
| `job_batches` | Batch jobs |
| `failed_jobs` | Mislukte jobs |
| `personal_access_tokens` | API tokens |

### Rollen en rechten (Spatie Permission)
| Tabel | Beschrijving |
|-------|--------------|
| `permissions` | Permissies |
| `roles` | Rollen |
| `model_has_permissions` | Koppeling model–permissie |
| `model_has_roles` | Koppeling model–rol |
| `role_has_permissions` | Koppeling rol–permissie |

### Core
| Tabel | Beschrijving |
|-------|--------------|
| `modules` | Registry van geïnstalleerde modules |

### Bedrijven
| Tabel | Beschrijving |
|-------|--------------|
| `companies` | Bedrijven |
| `company_locations` | Vestigingen/locaties |

### Notificaties en e-mail
| Tabel | Beschrijving |
|-------|--------------|
| `notifications` | Notificaties |
| `email_templates` | E-mailtemplates |

### Betalingen
| Tabel | Beschrijving |
|-------|--------------|
| `payment_providers` | Betalingsproviders |
| `payments` | Betalingen |
| `invoices` | Facturen |
| `invoice_settings` | Factuurinstellingen |
| `payment_reminders` | Betalingsherinneringen |

### Frontend
| Tabel | Beschrijving |
|-------|--------------|
| `frontend_themes` | Frontendthema’s |
| `website_pages` | Website-pagina’s |
| `website_media` | Media voor website |

### Chat
| Tabel | Beschrijving |
|-------|--------------|
| `chats` | Chats |
| `chat_rooms` | Chatruimtes |
| `chat_messages` | Chatberichten |
| `chat_participants` | Chatdeelnemers |
| `typing_indicators` | Typing-indicators |
| `chat_history` | Chatgeschiedenis (legacy) |

### Overig
| Tabel | Beschrijving |
|-------|--------------|
| `general_settings` | Algemene instellingen |
| `account_activation_tokens` | Accountactivatietokens |

### Categorieën/branches (basis)
| Tabel | Beschrijving |
|-------|--------------|
| `categories` | Categorieën (later hernoemd naar `branches`) |

---

## Module-specifieke tabellen (niet standaard)

Deze tabellen horen bij één module en zouden alleen in de database van die module (of in de hoofddatabase bij die module) moeten bestaan.

### Nexa Skillmatching
| Tabel | Beschrijving |
|-------|--------------|
| `job_titles` | Functietitels |
| `branch_functions` | Branchefuncties |
| `branch_function_skills` | Vaardigheden per branchefunctie |
| `job_configurations` | Jobconfiguraties |
| `job_configuration_types` | Types jobconfiguratie |
| `vacancies` | Vacatures |
| `candidates` | Kandidaten |
| `matches` | Matches vacature–kandidaat |
| `applications` | Sollicitaties |
| `interviews` | Gesprekken |
| `stage_types` | Fase-types (pipeline) |
| `stage_instances` | Fase-instanties |
| `pipeline_templates` | Pipelinetemplates |
| `candidate_activities` | Activiteiten op kandidaten |
| `candidate_embeddings` | Embeddings kandidaten |
| `candidate_texts` | Teksten kandidaten |
| `vacancy_embeddings` | Embeddings vacatures |
| `experiences` | Ervaringen |
| `skills` | Vaardigheden |
| `cv_files` | CV-bestanden |
| `favorites` | Favorieten |

De kolom `job_title_id` / `function` op `users` is ook Skillmatching-specifiek (uit de migratie `add_function_to_users_table`).

### Taxi Royaal
| Tabel | Beschrijving |
|-------|--------------|
| `vehicles` | Voertuigen |
| `ride_requests` | Ritverzoeken |

---

## Migratie-paden en gedrag

Migraties zijn opgesplitst in:

- **`database/migrations/core`** – o.a. `modules`-tabel (wordt als eerste gedraaid).
- **`database/migrations/shared`** – standaard tabellen (users, companies, permissions, notifications, chats, payments, branches, website, enz.).
- **`database/migrations/modules/taxiroyaal`** – alleen `vehicles` en `ride_requests` (+ aanpassingen).
- **`database/migrations/modules/skillmatching`** – alleen Skillmatching-tabellen (vacancies, candidates, matches, interviews, enz.).

Configuratie: `config/module_migrations.php` (paths en `module_migration_sets`).

### Hoofddatabase (nexa)

Voor een **nieuwe of volledige install** van de hoofddatabase:

```bash
php artisan migrate:all
```

Dit draait achtereenvolgens: core → shared → modules/taxiroyaal → modules/skillmatching. Daarna eventueel `db:seed` (RoleSeeder) voor rollen/superadmin op de hoofddatabase.

Standaard `php artisan migrate` gebruikt alleen het pad `database/migrations` (root); voor een volledig schema gebruik `migrate:all`.

### Module-databases (nexa_taxiroyaal, nexa_skillmatching)

- Bij **installatie van een module** wordt een **eigen database** aangemaakt (bijv. `nexa_taxiroyaal`).
- Op die database worden **alleen** de migraties voor die module gedraaid:
  - **nexa_taxiroyaal**: core + shared + modules/taxiroyaal (geen Skillmatching-tabellen).
  - **nexa_skillmatching**: core + shared + modules/skillmatching (geen vehicles/ride_requests).
- Daarna wordt alleen de **superadmin** ge seed (RoleSeeder); rechten en rollen zijn gebaseerd op de **pagina’s van die module** (via `registerPermissions()`). Tabellen blijven verder leeg.
