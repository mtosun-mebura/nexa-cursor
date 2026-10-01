# NEXA Taxi Network – Implementation & Security Specification

> Status: implementation specification for Cursor. Security and payment correctness take precedence over delivery speed.
> Scope: extend the existing NEXA Taxi module; do not rewrite working taxi, marketplace, dispatch, payment or pagebuilder functionality.

## 1. Non-negotiable architecture rules

1. Analyse existing code before implementation.
2. Reuse `app/Modules/NexaTaxi`, `RideRequest`, `RideDispatchOffer`, `RideDispatchService`, `RideClaimService`, `TaxiDriverEligibilityService`, `TaxiRidePaymentService`, `RidePayment`, `RideTrackService`, marketplace billing and `CentralWelcomePageService`.
3. No destructive migration or tenant-isolation regression.
4. Existing tenant bookings and `nexasuite.nl/boek` marketplace bookings must remain compatible.
5. NEXA is technology platform + optional marketplace + optional taxi network. The booking owner remains distinct from the executing company.
6. Security is deny-by-default. Never trust role, company, fare, payout, ride completion, GPS or payment state supplied by a client.

## 2. Existing functionality to preserve

The repository already contains dispatch offers/waves/expiry, atomic ride claiming with transactions and row locks, driver availability, marketplace candidate companies, payments, ride tracking, marketplace billing, and WebsitePage/pagebuilder marketing pages including `/taxi` and `/boek`.

Before creating any table/class/field, search for an existing equivalent.

## 3. Ride ownership and NEXA Network

Support:
- tenant ride: customer books with a taxi company; own fleet fulfils;
- NEXA marketplace ride: customer books via NEXA; an eligible company claims/fulfils;
- network ride: customer books with Taxi A; Taxi A cannot fulfil; Taxi B may execute without taking ownership of Taxi A's customer.

Keep `company_id` as booking/customer owner where already set. Add `fulfilling_company_id` only if no equivalent exists. For a network ride, accepting an offer sets the executing company/driver but MUST NOT replace the owner `company_id`.

Network must default OFF for existing tenants. Prefer existing settings architecture for:
- network_enabled
- network_mode: off | manual | auto
- network_fallback_seconds
- network_max_radius_km
- network fee configuration

Extend `RideDispatchService`; do not create a second dispatch engine. Reuse candidate/resolver and eligibility logic. Extend `RideClaimService` while retaining atomic acceptance/locking.

## 4. Self-service registration

The public/mobile registration flow must offer explicit account types:
- Customer
- Driver
- Taxi company / company administrator (if business onboarding is enabled)

Do NOT accept an arbitrary `role`, `roles[]`, `company_id`, permissions array, admin flag, driver verification flag, payout status, or subscription state from the request.

### Customer registration
Server creates the account and assigns exactly the canonical customer role. Required baseline:
- normalized unique email;
- password policy using Laravel's current secure password facilities;
- email verification before sensitive actions;
- rate limiting and bot/abuse protection;
- session rotation after authentication;
- generic auth/reset errors to prevent account enumeration;
- MFA/passkeys should be supported for sensitive account changes when available.

A customer can never promote themselves through profile/API payloads.

### Driver registration
Server assigns only a restricted `driver_pending`/equivalent onboarding state. It MUST NOT assign an operational driver role merely because the user selected “Driver”.

Driver progression:
`registered -> email_verified -> identity_pending -> identity_verified -> taxi_credentials_pending -> payout_pending -> review_pending -> approved -> active`

Only server-side onboarding services/admin/provider webhooks may advance privileged verification states. Client requests can submit evidence, never assert verification.

### Taxi company registration
Business self-registration should create a pending company + pending company administrator, not an immediately trusted tenant admin. Verify company/KVK/business data and payment/KYB readiness before activation. Never let a registrant attach themselves to an existing `company_id` by guessing an ID.

## 5. Authorization and anti-hack requirements

Use policies/gates/middleware on every privileged endpoint. UI hiding is not authorization.

Mandatory:
- object-level authorization for every ride, offer, customer, driver, company, payment and payout;
- tenant scope enforced server-side;
- UUID/public tracking tokens must be high entropy and narrowly scoped;
- CSRF protection for cookie-authenticated web flows;
- strict CORS;
- rate limits for login, registration, verification, password reset, offer accept/decline, ride state changes and payout changes;
- replay protection/idempotency keys for state-changing payment/ride operations;
- provider webhook signature/verification where supported, then fetch authoritative object state from provider;
- no mass assignment of privileged fields;
- no sequential-ID authorization assumptions;
- audit log for role, verification, payout destination, ride status, fare and settlement changes;
- secrets and sensitive payout data never in logs.

Role assignment must live in a dedicated server-side service, not generic profile update code.

## 6. Driver identity and eligibility

Before a driver may receive network rides, require evidence appropriate to the legal/business model. At minimum design fields/states for:
- verified identity;
- phone/email;
- driver/taxi credentials where applicable;
- company affiliation or approved independent-provider relationship;
- vehicle eligibility;
- payout/KYB status;
- terms acceptance version/timestamp/IP or equivalent audit metadata;
- suspension/risk status.

Do not invent document-validity rules in code. Make verification requirements configurable and reviewable.

## 7. Payout onboarding: never store raw bank/card credentials

NEXA MUST NOT store raw card PAN/CVC and should not store payout banking credentials when a PSP-hosted onboarding/tokenized account can be used.

Use a payment provider connected-account/merchant model and store only identifiers/status metadata such as:
- provider
- provider_account_id / organization_id
- payout_capability_status
- masked destination display data returned by provider when permitted
- verified_at / disabled_at

Do not expose or persist CVC. Never build a custom “credit card number for payout” field.

Important: determine the supported payout model before implementation. Mollie Connect Marketplace onboarding is for legal entities; private individuals cannot simply be treated as connected sub-merchants. If drivers are employees/contractors of a taxi company, the taxi company may be the settlement party and pay its drivers. If independent drivers are to receive funds directly, validate that the chosen PSP/legal model supports that exact seller type before coding it.

Payout destination changes are high risk: require recent authentication + MFA/step-up, provider re-verification where possible, security notification, audit trail and a cooling-off/hold period before new destination can receive funds.

## 8. Never pay solely because a driver clicked “complete”

A driver-controlled `complete` action is only a claim that the ride ended. It is NOT sufficient evidence for settlement.

Introduce an authoritative settlement gate, e.g.:
`accepted -> en_route -> arrived -> passenger_on_board -> completion_claimed -> completion_verified -> settlement_eligible -> settled`

The exact existing RideRequest statuses may differ; map to existing state machines rather than duplicating them.

### Completion evidence
Use multiple independent signals, risk-weighted:
- customer payment is confirmed/authorized/captured as appropriate;
- trip start was recorded server-side;
- plausible elapsed time since start;
- GPS/track evidence is consistent with pickup and destination;
- actual route/distance/duration is plausible versus quote;
- driver/device location events have server timestamps and cannot be backdated by client;
- customer confirmation/rating/receipt interaction where available;
- no active cancellation/refund/dispute;
- no impossible overlapping driver rides;
- no repeated suspicious device/account/location patterns;
- for cash rides, stronger/manual verification because payment confirmation is weaker.

Never require every signal blindly; use a risk engine so legitimate GPS loss does not make completion impossible.

## 9. Anti-fraud controls

Implement a `RideSettlementEligibilityService` (name may change to match conventions) that is the ONLY path to creating a payable settlement.

It must evaluate immutable/server-derived facts, not request booleans.

Risk flags should include:
- completion too soon after acceptance/start;
- pickup not reached;
- destination never approached;
- route/distance materially implausible;
- GPS teleportation/impossible speed;
- same device used by suspiciously many driver accounts;
- repeated rides between same customer/driver with abnormal pattern;
- repeated cancellations/refunds after payout;
- driver/customer/company collusion indicators;
- excessive manual fare increases;
- overlapping active rides;
- payout destination recently changed;
- newly onboarded driver with high-value rides;
- repeated customer non-confirmation/complaints.

Risk outcomes:
- auto approve;
- hold for delayed verification;
- manual review;
- reject settlement / suspend pending investigation.

Never automatically accuse a user of fraud solely from one heuristic.

## 10. Settlement hold and delayed routing

Prefer delayed settlement/routing. Do not make funds withdrawable immediately after `completion_claimed`.

Configuration should support:
- normal settlement hold;
- longer hold for new/high-risk drivers;
- dispute/refund reserve;
- manual hold;
- release after verified completion.

Once a settlement is released, store an immutable snapshot of gross amount, platform/network fee, executor amount, owner amount, currency, provider IDs and rules/version used.

Corrections/refunds use compensating ledger entries; never rewrite financial history.

## 11. Customer confirmation

Where UX allows, after a driver claims completion send the customer a server-generated confirmation:
- “Rit afgerond” with receipt;
- allow “probleem melden” within a defined window;
- optionally one-tap confirmation.

Customer confirmation is a useful independent signal, but absence alone should not block every legitimate trip. Customer and driver must not be able to directly set `settlement_eligible`.

## 12. GPS integrity

Client GPS is evidence, not absolute truth. Store server receive timestamp separately from device timestamp. Reject grossly stale/future timestamps. Apply plausibility checks and sequence constraints. Do not let the client submit arbitrary historical tracks as authoritative completion evidence.

Where mobile capabilities permit, use device/app attestation as an additional risk signal, never as the sole authorization mechanism.

## 13. Payments and ledger

Inspect existing `TaxiRidePaymentService`, `RidePayment`, marketplace billing and any ledger before adding financial tables.

Keep marketplace fee and network fulfilment fee conceptually separate.

Historical fee data must be snapshotted. Do not recalculate old settlements from today's configuration.

Payment webhooks are notifications, not trusted final payloads: verify and fetch authoritative payment state from the PSP.

## 14. Privacy

Collect only data needed for account, safety, ride execution, legal reporting and payments. Define retention periods. Restrict partner-company visibility to the minimum operational data needed for a specifically offered/accepted network ride. Do not expose customer history, invoices or internal owner-company notes to another tenant.

High-risk identity documents should preferably remain with the verification/payment provider rather than in NEXA storage.

## 15. DAC7 / platform reporting readiness

Because NEXA may connect service providers with customers and facilitate consideration, design seller/provider records so legally required identity, transaction and consideration data can be exported/reported if NEXA falls within DAC7. Do not hardcode a conclusion that every driver is reportable; make the data model auditable and obtain legal/tax validation before production launch.

## 16. Public /taxi page

Extend existing `CentralWelcomePageService::defaultTaxiSections()` and pagebuilder patterns, not a separate hardcoded Blade page.

Positioning:
**Nexa Taxi: jouw taxibedrijf. Eén slim netwerk.**

Explain:
- own brand/customers/tariffs remain with tenant;
- own fleet first;
- optional NEXA Network when capacity is unavailable;
- marketplace bookings from NEXA are distinct;
- one driver app;
- customer booking/tracking/payment;
- transparent configurable fees.

Do not overwrite manually customized production `home_sections`. Build an idempotent, safe migration/synchronization strategy.

SEO:
Title: `Nexa Taxi - Taxi software, dispatch en netwerk voor taxibedrijven`
Description: `Beheer boekingen, chauffeurs, betalingen en dispatch vanuit één platform. Gebruik je eigen vloot en schaal optioneel op via het NEXA Network.`

## 17. Tests required before production

Add/retain tests for:
- customer self-registration always receives only customer permissions;
- driver registration starts pending and cannot self-promote;
- malicious `role=admin`, `company_id`, `verified=true`, payout status etc. are ignored/rejected;
- tenant/company registration cannot hijack existing tenant IDs;
- IDOR attempts across users/companies;
- rate limiting;
- email verification;
- payout destination cannot be changed without step-up authorization;
- no raw card/CVC storage;
- network disabled regression;
- network fallback and partner acceptance;
- owner company preserved; fulfiller set separately;
- two drivers accepting same offer -> one winner;
- driver cannot settle by calling complete endpoint;
- impossible/too-short/fake ride -> hold/review, no payout;
- legitimate completed ride -> eligible after required evidence/hold;
- overlapping rides rejected/flagged;
- webhook replay/idempotency;
- refund after settlement creates compensating financial record;
- marketplace booking/billing regression;
- tenant isolation and least-privilege partner visibility.

Include adversarial feature tests, not only happy paths.

## 18. Cursor execution order

### Phase 0 — analysis only
Read all relevant auth/registration/roles, company membership, taxi, dispatch, tracking, payment, billing, pagebuilder and tests. Report:
1. what already exists;
2. what partially exists;
3. what is missing;
4. current role/permission model;
5. current registration endpoints;
6. current driver/company relationship;
7. current payout/payment architecture;
8. current ride completion path;
9. concrete security gaps;
10. exact minimal migrations/classes to change.

Do not perform a broad rewrite before this analysis.

### Phase 1 — secure registration
Implement canonical server-side role assignment, verification states, policies, throttling and tests.

### Phase 2 — provider/KYB/payout onboarding
Implement provider-linked payout identities/capabilities; no raw payout card storage.
→ See [NEXA_TAXI_NETWORK_PHASE2.md](./NEXA_TAXI_NETWORK_PHASE2.md) (done on `feature/nexa-taxi`; Mollie Connect live API still stub).

### Phase 3 — network owner/fulfiller model
Minimal additive schema and dispatch/claim changes.
→ See [NEXA_TAXI_NETWORK_PHASE3.md](./NEXA_TAXI_NETWORK_PHASE3.md) (done on `feature/nexa-taxi`).

### Phase 4 — verified completion and settlement gate
Implement evidence/risk evaluation, hold/review states; billing/earnings gated on `settlement_eligible`.
→ See [NEXA_TAXI_NETWORK_PHASE4.md](./NEXA_TAXI_NETWORK_PHASE4.md) (done on `feature/nexa-taxi`; ledger snapshot still later).

### Phase 5 — planner/driver/customer UX
Expose only safe controls and status. Never make UI state authoritative.
→ See [NEXA_TAXI_NETWORK_PHASE5.md](./NEXA_TAXI_NETWORK_PHASE5.md) (done on `feature/nexa-taxi`).

### Phase 6 — /taxi marketing page
Use existing pagebuilder with safe idempotent synchronization.
→ See [NEXA_TAXI_NETWORK_PHASE6.md](./NEXA_TAXI_NETWORK_PHASE6.md) (done on `feature/nexa-taxi`).

### Phase 7 — adversarial/security test pass
Run relevant Laravel tests and project lint/format tools. Separate pre-existing failures from new failures.
Core unit/feature tests for phases 2–6 are in place; full adversarial matrix remains ongoing.

## 19. Definition of Done

Done means:
- no user can choose/administer arbitrary roles or tenant IDs;
- customers and drivers can self-register safely;
- drivers cannot become active before required verification;
- payout destination is provider-verified/tokenized;
- raw card/CVC is never stored;
- clicking “complete” cannot by itself cause payout;
- settlement requires server-side evidence/risk gate and hold;
- suspicious rides are held/reviewed;
- booking owner and fulfiller remain distinct;
- tenant isolation remains intact;
- marketplace and network fees remain distinct;
- existing taxi/marketplace flows still pass regression tests;
- /taxi explains the model through existing pagebuilder;
- terms/privacy acceptance is versioned and auditable.

Security wins over convenience. When a requirement conflicts with least privilege, payment-provider rules, tenant isolation or financial integrity, stop and document the conflict before implementing.
