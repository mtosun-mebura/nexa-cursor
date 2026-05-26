# Tenant onboarding — implementatie & stappenplan

De **wizard** in de admin (`Bedrijven` → **Nieuwe tenant (wizard)**) volgt onderstaande volgorde. Tabs tonen de voortgang: **alleen voltooide stappen en de huidige stap** zijn klikbaar om terug te gaan; **toekomstige stappen** zijn uitgeschakeld totdat je met **Volgende** doorwerkt.

## Stappen (tabs)

| # | Tab | Inhoud |
|---|-----|--------|
| 1 | **Bedrijf & logo** | Bedrijfsnaam, KVK, branche, type, website, beschrijving, contact, adres, logo, actief/hoofdkantoor-vlaggen. |
| 2 | **Vestigingen** | Eerste vestiging(en): naam, adres, contact, hoofdvestiging. Optioneel overslaan als later ingevuld. |
| 3 | **Domein** | Tenant-hostname(s), primair domein; korte toelichting DNS/SSL (concept). |
| 4 | **Modules** | Modules koppelen aan dit bedrijf; niet-geïnstalleerde module installeren; niet-actieve module activeren (via `ModuleManager`). |
| 5 | **Gebruikers & rollen** | Link naar gebruikers aanmaken; korte uitleg eerste admin / rollen (Spatie). |
| 6 | **Frontend / website** | Link naar website-pagina’s / builder (indien beschikbaar voor de rol). |
| 7 | **Afronden** | Samenvatting + knop naar bedrijfsdetail. |

## Aanvullende stappen (aanbevolen, later uit te breiden)

- **Notificaties & e-mail** — afzenderdomein, templates per tenant (fase 2).
- **Facturatie / abonnement** — tenant-abonnement koppelen (fase 2).
- **API-sleutels / integraties** — externe koppelingen (fase 2).

## Technisch

- Sessiesleutel per bedrijf: `company_wizard.{id}.max_reachable` — hoogste stap die geopend mag worden.
- Routes: zie `routes/web.php` (`admin.companies.wizard.*`).
- Zie Blade: `resources/views/admin/companies/wizard/`.
- **Tab-navigatie:** de UI volgt het gevraagde patroon (onderstreepte tabs, actieve tab met merk-kleur, toekomstige stappen `cursor-not-allowed`), met projectclasses (`border-input`, `text-primary`, `ki-filled`-iconen) voor consistentie met de admin.

---

*Aansluitend op [`saas-multi-tenant-prompt.md`](./saas-multi-tenant-prompt.md).*
