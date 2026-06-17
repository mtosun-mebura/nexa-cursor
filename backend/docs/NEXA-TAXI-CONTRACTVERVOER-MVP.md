# Nexa Taxi — Contractvervoer MVP

Datum: 2026-06-16  
Scope: abonnementen, groepsritten (multi-stop), individuele contractritten, chauffeursapp, maandfacturatie.

---PAGE---

## 1. Doel van de MVP

Een taxibedrijf kan met deze MVP:

1. Een contractklant aanmaken (bijv. School A) met een abonnement en maandprijs.
2. Passagiers individueel vastleggen en in groepen plaatsen.
3. Per groep een vaste multi-stop route plannen (bijv. 10 adressen naar school).
4. Daarnaast individuele contractritten inplannen onder hetzelfde abonnement.
5. Ritten automatisch laten verschijnen bij de vaste chauffeur in de chauffeursapp.
6. Chauffeur voert ritten uit via stoplijst (groep) of standaard ritflow (individueel).
7. Maandelijks een factuur genereren voor de contractklant.
8. Een SEPA-mandaat vastleggen (automatische incasso volgt in fase 2).

Kernprincipe: elke uitvoering wordt een bestaande ride_request. De chauffeursapp blijft werken met start en complete.

---PAGE---

## 2. Scope

### In de MVP

| Onderdeel | Beschrijving |
| --- | --- |
| Contractklant + abonnement | Eén klant, één actief abonnement |
| Passagiers | Individueel vastgelegd, koppelbaar aan groepen |
| Groepen | CRUD, leden toevoegen/verwijderen, eindlocatie bewerken |
| Groepsroute | Multi-stop, vaste weekdagen, routeberekening |
| Individuele contractrit | Handmatig gepland, valt onder abonnement |
| Vaste chauffeur + voertuig | Per groep/route of per losse rit |
| Ritgeneratie | 14 dagen vooruit |
| Chauffeursapp | Stoplijst (groep) + normale rit (individueel) |
| Maandfactuur | Eén factuur per maand per contract |
| SEPA-mandaat | IBAN + mandaatstatus vastleggen |

### Buiten de MVP (later)

- Live GPS-herroutering tijdens de rit
- Volledig geautomatiseerde SEPA-incasso via webhook
- Passagiers- of ouderportaal
- Meerdere actieve abonnementen per klant
- Volledige TSP-optimalisatie van routes
- WMO/gemeente-rapportages
- Website-boekingen die automatisch aan contract hangen

---PAGE---

## 3. Voorbeeld: School A

School A heeft een ochtendgroep met 10 kinderen. Elk kind heeft een eigen ophaaladres. Alle kinderen worden naar hetzelfde schooladres gebracht.

Het taxibedrijf legt vast:

- Klant: School A (contractklant, factuuradres, debiteurnummer)
- Abonnement: maandelijks vast bedrag + optioneel prijs per extra rit
- Groep: Ochtendgroep A met 10 passagiers
- Eindlocatie: schooladres, aankomst om 08:00
- Route: startpunt chauffeur (depot of eerste stop) → 10 stops → school
- Chauffeur + voertuig: vast gekoppeld aan de groep

Daarnaast kan de planner onder hetzelfde abonnement een individuele rit boeken, bijvoorbeeld een extra rit naar de fysiotherapeut voor één kind.

---PAGE---

## 4. Domeinmodel

```
TransportCustomer (School A)
  └── TransportContract (abonnement)
        ├── TransportPassengers (kinderen, individueel)
        ├── TransportGroups (groepen)
        │     ├── GroupMembers (passagier ↔ groep)
        │     └── TransportRouteTemplate (multi-stop, recurrent)
        │           ├── RouteStops (adressen + volgorde + tijd)
        │           └── TransportAssignment (vaste chauffeur + voertuig)
        │
        └── TransportIndividualBookings (losse contractritten)

        ↓ scheduler / handmatig

TransportOccurrence (geplande uitvoering op datum)
  └── RideRequest (bestaand model → chauffeursapp)
        └── RideStops (alleen bij multi-stop groepsrit)
```

---PAGE---

## 5. Twee rittypes onder één abonnement

### Type A — Groepsrit (multi-stop)

| Veld | Waarde |
| --- | --- |
| ride_type | contract_group |
| Stops | Meerdere ophaalpunten + eindpunt (school) |
| Chauffeur | Vast per groep |
| Betaling | payment_method = contract |
| Facturatie | Meerekenen op maandfactuur |

### Type B — Individuele contractrit

| Veld | Waarde |
| --- | --- |
| ride_type | contract_individual |
| Stops | Eén ophaal → één afzet |
| Passagier | Eén gekozen passagier |
| Planner | Handmatig datum, tijd, chauffeur |
| Betaling | payment_method = contract |
| Facturatie | Meerekenen op maandfactuur |

Beide types: geen dispatch-pool, geen betaling in de chauffeursapp.

---PAGE---

## 6. Database — nieuwe tabellen

### Klant en abonnement

- transport_customers — naam, contact, factuuradres, debiteurnummer
- transport_contracts — tarief, looptijd, facturatiedag, billing_model
- transport_payment_mandates — IBAN, mandaatreferentie, status (MVP: vastleggen)

### Passagiers en groepen

- transport_passengers — naam, ophaaladres, opmerkingen
- transport_groups — naam, eindlocatie school, aankomsttijd doel
- transport_group_members — koppeling passagier ↔ groep met valid_from/valid_until

### Groepsroute

- transport_route_templates — weekdagen, startpunt chauffeur, route_locked
- transport_route_stops — volgorde, adres, passagier, geplande tijd
- transport_assignments — vaste chauffeur + voertuig

### Individuele ritten

- transport_individual_bookings — passagier, adressen, pickup_at, chauffeur

### Uitvoering

- transport_occurrences — geplande rit op datum, koppeling naar ride_request
- ride_stops — stops per ride_request (nieuw)

### Uitbreiding ride_requests

- source: booking | contract | manual
- ride_type: standard | contract_group | contract_individual
- transport_contract_id, transport_occurrence_id
- payment_method: contract (geen Mollie/contant in app)

---PAGE---

## 7. Admin — schermen MVP

### Contractklanten

Pad: /admin/taxi/contract-customers  
Lijst, aanmaken, bewerken. Contact- en factuurgegevens. SEPA-mandaat.

### Abonnement

Pad: /admin/taxi/contracts/{id}  
Tariefmodel (vast / per rit / hybride), looptijd, facturatiedag. Overzicht groepen, losse ritten, facturen.

### Passagiers

Pad: /admin/taxi/contracts/{id}/passengers  
CRUD per passagier. Ophaaladres. Koppeling aan groepen.

### Groepen

Pad: /admin/taxi/contracts/{id}/groups  
CRUD groep. Eindlocatie bewerken. Leden toevoegen en verwijderen (met valid_until bij uitschrijving).

### Routeplanner groep

Pad: /admin/taxi/groups/{id}/route

1. Weekdagen kiezen (ma–vr)
2. Startpunt: depot of eerste stop
3. Leden worden stops
4. Route berekenen: volgorde + tijden
5. Handmatig aanpassen
6. Route vastzetten
7. Vaste chauffeur + voertuig koppelen

### Individuele contractrit

Pad: /admin/taxi/contracts/{id}/bookings/new  
Passagier, adressen, datum/tijd, chauffeur, optionele ritprijs.

### Planningsoverzicht

Kalender per week: groepsritten en individuele ritten met status.

### Facturatie

Maandfactuur genereren, PDF, e-mail. SEPA-mandaatstatus tonen.

---PAGE---

## 8. Routeberekening (MVP)

```
Input:
  - stops[] (passagier-adressen)
  - destination (school)
  - destination_arrival_time (08:00)
  - driver_start (depot of eerste stop)
  - buffer_per_stop (2 min)

Stap 1 — Volgorde
  - Start vanaf driver_start
  - Nearest-neighbor voor alle pickup-stops
  - Destination altijd als laatste stop

Stap 2 — Tijden (achteruit rekenen)
  - School om 08:00
  - Vorige stop = 08:00 − rijtijd − buffer
  - Herhaal tot eerste stop
  - Waarschuwing als vertrek te vroeg onrealistisch is

Stap 3 — Opslaan in transport_route_stops
```

Handmatige override: planner past volgorde aan. Na vastzetten alleen tijden herberekenen. Geen live herroutering in MVP.

---PAGE---

## 9. Scheduler (dagelijkse job)

GenerateContractOccurrencesJob — elke nacht om 04:00.

### Groepsritten

Voor elke actieve transport_route_template:

1. Check recurrence (komende 14 dagen)
2. Skip feestdagen/uitzonderingen
3. Maak transport_occurrence
4. Maak ride_request (status accepted, payment_method contract)
5. Kopieer route_stops naar ride_stops

### Individuele ritten

Voor elke transport_individual_booking met status planned en pickup_at binnen 14 dagen:

1. Maak occurrence + ride_request (contract_individual)
2. Geen ride_stops (enkele ophaal/afzet)

Geen dispatch-offers voor contractritten.

---PAGE---

## 10. Chauffeursapp (MVP)

### Groepsrit (contract_group)

Ritkaart in Geplande ritten:

School A – Ochtend (10 stops)  
Vertrek 07:05 | School 08:00 | Badge Contract

Stoplijst met per stop: passagier, adres, tijd, knoppen Opgehaald en Afwezig.  
Start rit → navigatie naar actieve stop.  
Rit afronden na laatste stop. Geen betalingspaneel.

### Individuele contractrit (contract_individual)

Zelfde UI als huidige geplande rit: één ophaal, één afzet, badge Contract, geen betaling.

### Nieuwe API-endpoints

- GET /dispatch/rides/{id}/stops
- POST /dispatch/rides/{id}/stops/{stop}/arrive
- POST /dispatch/rides/{id}/stops/{stop}/pickup
- POST /dispatch/rides/{id}/stops/{stop}/skip

Bestaande start en complete blijven. Bij groepsrit vereist complete dat alle stops picked_up of skipped zijn.

---PAGE---

## 11. Facturatie MVP

### Maandfactuur (ContractInvoiceService)

Per contract en periode (YYYY-MM):

- billing_model fixed_monthly: regel met maandbedrag
- billing_model per_ride of hybrid: tel afgeronde groeps- en individuele ritten × prijs
- Maak Invoice (bestaand model), module taxi_contract, PDF, status sent

### SEPA (MVP)

- Mandaat vastleggen in admin (IBAN, status active/pending/revoked)
- Factuur vermeldt incasso via SEPA indien mandaat actief
- Geen automatische incasso-batch in MVP — handmatig betaald markeren of export
- Fase 2: Mollie SEPA Direct Debit

---PAGE---

## 12. Implementatievolgorde (8–10 weken)

| Week | Onderdeel | Deliverable |
| --- | --- | --- |
| 1 | Migraties + modellen | transport_* tabellen, ride_stops, ride_requests uitbreiding |
| 2 | Admin klant, contract, passagiers | CRUD basis |
| 3 | Admin groepen + leden | Groepsbeheer |
| 4 | Admin routeplanner | Multi-stop, berekening, vastzetten |
| 5 | Scheduler + occurrences | Groepsritten → ride_requests |
| 6 | Admin individuele contractritten | Booking + generatie |
| 7 | Chauffeursapp groepsrit | Stop-API + stoplijst UI |
| 8 | Chauffeursapp individueel | Contract badge, geen betaling |
| 9 | Maandfactuur + mandaat | ContractInvoiceService, PDF |
| 10 | Test + pilot | 1 school, 1 groep, 1 chauffeur |

---PAGE---

## 13. Acceptatiecriteria MVP

### School A — groepsrit

- 10 passagiers in groep, school als eindpunt
- Route berekend met volgorde en tijden
- Ma–vr automatisch 14 dagen vooruit gegenereerd
- Chauffeur ziet stoplijst en kan per stop afvinken
- Rit afgerond zonder betalingsscherm

### Individuele contractrit

- Planner plant extra rit voor één passagier onder zelfde abonnement
- Chauffeur ziet rit als geplande contractrit
- Start en afronden werken zoals normale rit

### Facturatie

- Maandfactuur met vast bedrag en optionele rit-telling
- PDF naar contractklant
- SEPA-mandaat vastgelegd en zichtbaar op contract

### Beheer

- Passagier toevoegen/verwijderen uit groep zonder historie kwijt
- Eindlocatie groep aanpasbaar
- Route opnieuw berekenen na wijziging leden

---PAGE---

## 14. Samenvatting

| Vraag | MVP-antwoord |
| --- | --- |
| Groepen met multi-stop? | Ja, via transport_groups + route_stops |
| Individuele ritten onder abonnement? | Ja, via transport_individual_bookings |
| Zelfde abonnement/factuur? | Ja, beide onder transport_contract |
| Chauffeursapp? | Groep = stoplijst; individueel = bestaande ritflow |
| Automatische route + tijden? | Ja, nearest-neighbor + achteruit vanaf schooltijd |
| Maandfactuur? | Ja |
| Incasso? | Mandaat vastleggen; automatische incasso in fase 2 |

Volgende stap na akkoord: week 1 — migraties en modellen implementeren.
