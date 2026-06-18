# Nexa Taxi Contractvervoer — Pilot & acceptatietest

Datum: 2026-06-16  
Doel: formele doorloop van acceptatiecriteria uit §13 met 1 school, 1 groep, 1 chauffeur.

## Implementatiestatus (code)

De MVP-scope (week 1–9) is geïmplementeerd. Voor deploy:

```bash
cd backend
php artisan migrate --force
php artisan modules:migrate taxi
php artisan taxi:generate-contract-occurrences --days=14   # handmatig testen
```

Scheduler (cron `php artisan schedule:run`):

- `04:00` — contract-occurrences (14 dagen vooruit)
- `05:00` — contractfacturen op factuurdag (vorige maand)

Unit tests: `php artisan test --filter='ContractInvoice|RideClaim|TransportScheduleException'`

## Testopzet

| Rol | Gegevens |
| --- | --- |
| Contractklant | School A (factuuradres, debiteurnummer, contact-e-mail) |
| Abonnement | Actief, `fixed_monthly` of `hybrid`, factuurdag ingesteld |
| Groep | Ochtendgroep met 10 passagiers, school als eindpunt 08:00 |
| Chauffeur | Vaste chauffeur + voertuig op route-template |
| Planner | Admin-gebruiker met `rides.view` / `rides.update` |

## Checklist — School A groepsrit

- [ ] 10 passagiers aangemaakt en gekoppeld aan groep
- [ ] Route berekend (volgorde + tijden), route vastgezet
- [ ] Scheduler / `php artisan taxi:generate-contract-occurrences` genereert ma–vr 14 dagen vooruit
- [ ] Feestdag/uitzondering: geen occurrence op ingestelde uitzonderingsdag
- [ ] Chauffeur ziet groepsrit in Geplande ritten (alleen vandaag startbaar)
- [ ] Stoplijst: Opgehaald / Afwezig per stop
- [ ] Rit afgerond zonder betalingsscherm
- [ ] Verlopen rit staat onder Verlopen, niet bij geplande ritten

## Checklist — Individuele contractrit

- [ ] Extra rit gepland voor één passagier onder zelfde abonnement
- [ ] Occurrence + ride_request gegenereerd (`contract_individual`)
- [ ] Chauffeur ziet rit als contractrit (badge Contract)
- [ ] Start en afronden werken (één ophaal, één afzet)
- [ ] Verlopen individuele contractrit: Rit afronden onder Verlopen

## Checklist — Facturatie

- [ ] SEPA-mandaat vastgelegd (IBAN, status actief)
- [ ] Maandfactuur handmatig gegenereerd op abonnementspagina
- [ ] PDF bevat regels volgens billing_model + SEPA-vermelding bij actief mandaat
- [ ] Factuur per e-mail verzonden naar contractklant
- [ ] Factuur handmatig als betaald gemarkeerd of CSV-export gedownload
- [ ] Automatische job op factuurdag: vorige maand gegenereerd (optioneel in staging)

## Checklist — Beheer

- [ ] Passagier toevoegen/uitschrijven uit groep (`valid_until`), historie behouden
- [ ] Eindlocatie groep aanpasbaar
- [ ] Route opnieuw berekenen na wijziging leden
- [ ] Planningsoverzicht toont week met status per rit
- [ ] Contractrit vrijgeven in API geblokkeerd (UI had geen knop)

## Commando's

```bash
cd backend
php artisan taxi:generate-contract-occurrences --days=14
php artisan schedule:run   # occurrences 04:00, facturen 05:00
```

## Afwijkingen / opmerkingen

| # | Omschrijving | Ernst | Status |
| --- | --- | --- | --- |
| 1 | | | |

## Akkoord

| Naam | Rol | Datum | Handtekening |
| --- | --- | --- | --- |
| | Planner | | |
| | Chauffeur | | |
| | Product owner | | |
