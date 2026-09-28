# NEXA Taxi Network — Phase 0 analysis

> Generated for `feature/nexa-taxi` from [NEXA_TAXI_NETWORK_IMPLEMENTATION.md](./NEXA_TAXI_NETWORK_IMPLEMENTATION.md).
> Analysis only; no network/dispatch/settlement schema changes in this phase.

## Headline

| Item | Status |
|------|--------|
| `fulfilling_company_id` | **Missing** |
| `network_*` settings | **Missing** (default OFF when added) |
| Marketplace claim | Sets owner `company_id` from offer when null — correct for marketplace, **not** for network owner≠fulfiller |
| Settlement gate | **Missing** — `complete` → `completed` can feed marketplace billing |
| Payout connected accounts | **Missing** |

## 1–3. Exists / partial / missing

**Exists:** `RideDispatchService`, `RideClaimService` (atomic lock), `TaxiDriverEligibilityService`, `TaxiRidePaymentService`/`RidePayment`, marketplace billing, `RideTrackService`, Spatie roles/teams, admin company wizard, `/taxi` via `CentralWelcomePageService`, API `POST /api/auth/register` → always `klant`.

**Partial:** owner vs executor (marketplace only); GPS (client `t` trusted); driver active flag; API register (no throttle, weak password, token before verify); cash complete path.

**Missing:** network model/settings/fees; multi-type public registration; `driver_pending` ladder; KYB/payout provider identities; settlement/risk/hold ledger; ride policies; adversarial registration tests; DAC7 seller export model.

## 4–8. Current model (summary)

- Roles: `super-admin`, `company-admin`, `staff`, `candidate`, `klant`, `chauffeur`(+aliases), `contractant`/`contractouder`. No `driver_pending`.
- Register: `AuthController::register` — ignores client role/company_id today; issues Sanctum token immediately.
- Driver↔company: `users.company_id` + Spatie team pivot; claim may set ride `company_id` from offer.
- Payments in: Mollie + cash. Driver payout: reporting only (`TaxiDriverEarningsService`), not PSP Connect.
- Complete path: `DriverDispatchController::complete` → `RideClaimService::completeRide` → `completed` (+ track finalize). No settlement evidence gate.

## 9. Security gaps (priority)

1. Public registration: weak password, unthrottled, token before email verify.
2. `User::$fillable` includes `company_id`, `is_active`, `email_verified_at` (admin/create paths rely on this — harden public paths first, fillable later).
3. Complete ≈ financially completed for marketplace billing.
4. Client GPS timestamps; stub 2FA success; limited object-level Ride policies.

## 10. Minimal additive change list

1. Phase 1: `PublicRegistrationService`, `driver_pending` role, throttle, password rules, no client privilege escalation, verification before token/login (reuse signed `/verify-email`).
2. Phase 3: `ride_requests.fulfilling_company_id`; extend claim/dispatch; network settings default off.
3. Phase 2/4: payout identities; settlement service + holds; GPS `received_at`.

## Phase 1 slice (this branch)

Secure registration + canonical roles only. **No** dispatch/claim/payment/schema changes.
