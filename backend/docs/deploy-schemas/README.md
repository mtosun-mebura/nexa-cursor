# Deploy schema's

Deze map bevat downloadbare schema's van het ingerichte auto-deployproces.
GitHub rendert de Mermaid-blokken in dit document direct als diagram.

## Downloadbare bestanden

- [deploy-overzicht.png](deploy-overzicht.png) - gecombineerde afbeelding voor Preview op macOS
- [deploy-overzicht.pdf](deploy-overzicht.pdf) - gecombineerde PDF voor Preview op macOS
- [deploy-overzicht.svg](deploy-overzicht.svg) - schaalbare bronafbeelding
- [01-branch-commit-pr-build-automerge-test.mmd](01-branch-commit-pr-build-automerge-test.mmd)
- [02-prod-tag-main-deploy.mmd](02-prod-tag-main-deploy.mmd)
- [03-create-prod-tag-main-only-fix.mmd](03-create-prod-tag-main-only-fix.mmd)

## 1. Commit op branch naar TEST-deploy

```mermaid
flowchart TD
    A[Developer pusht commit naar willekeurige werkbranch] --> B[Workflow: Test and Build]
    A --> C[Workflow: Open PR to release/test]
    C --> D{Bestaat er al een open PR?}
    D -- Ja --> E[PR hergebruiken]
    D -- Nee --> F[Maak PR: nexa-saas naar release/test]
    E --> G[Pull request naar release/test]
    F --> G
    G --> H[Workflow: Test and Build op PR]
    G --> I[Workflow: Auto-merge naar release/test]
    I --> J[Wacht op check test]
    H --> K{Check test succesvol?}
    J --> K
    K -- Nee --> L[Auto-merge stopt; geen TEST-deploy]
    K -- Ja --> M[Merge PR naar release/test]
    M --> N[Push-event op release/test]
    N --> O[Workflow: Deploy TEST Proxmox]
    O --> P[deploy/deploy-tenant.sh reset naar origin/release/test]
    P --> Q[Vite build, Docker Compose build/up]
    Q --> R[Laravel migrate, seed, cache optimize]
    R --> S[TEST staat live]
```

## 2. PROD-tag vanaf main naar PROD-deploy

```mermaid
flowchart TD
    A[Release/test is akkoord] --> B[PR of merge naar main]
    B --> C[Start workflow: Create PROD tag main only]
    C --> D{Workflow gestart vanaf branch main?}
    D -- Nee --> E[Fail-fast met instructie: kies Branch main]
    D -- Ja --> F[Checkout en fetch origin/main + tags]
    F --> G[Valideer semver tag vX.Y.Z]
    G --> H{Tag bestaat al lokaal of remote?}
    H -- Ja --> I[Stop; tagnaam moet uniek zijn]
    H -- Nee --> J[Maak annotated tag op origin/main SHA]
    J --> K[Push tag naar origin]
    K --> L[Wacht tot remote tag zichtbaar is]
    L --> M[Dispatch workflow: Deploy PROD met version + expected_sha]
    M --> N[Workflow: Deploy PROD]
    N --> O[Valideer tagnaam en expected_sha]
    O --> P[Checkout repo en fetch main + tags]
    P --> Q{Tag wijst naar expected_sha en zit op main-historie?}
    Q -- Nee --> R[Stop; verkeerde of onveilige tag]
    Q -- Ja --> S[SSH naar AWS Lightsail]
    S --> T[deploy/deploy-tenant.sh checkout tag]
    T --> U[Vite build, Docker Compose build/up]
    U --> V[Laravel migrate, seed, cache optimize]
    V --> W[PROD staat live]
```

## 3. Fix in Create PROD tag (main only)

```mermaid
flowchart TD
    A[Handmatige run: Create PROD tag] --> B[Controleer GITHUB_REF]
    B --> C{GITHUB_REF is refs/heads/main?}
    C -- Nee --> D[Stop met duidelijke foutmelding]
    C -- Ja --> E[Checkout main met volledige history]
    E --> F[Normaliseer versie: V1.2.3 naar v1.2.3]
    F --> G{Semver vX.Y.Z geldig?}
    G -- Nee --> H[Stop; ongeldige tagnaam]
    G -- Ja --> I[Fetch origin/main en tags]
    I --> J{Tag bestaat lokaal of remote?}
    J -- Ja --> K[Stop; tag bestaat al]
    J -- Nee --> L[Bepaal exacte origin/main commit-SHA]
    L --> M[Maak annotated tag op die SHA]
    M --> N[Push tag]
    N --> O[Poll remote tot tag zichtbaar is]
    O --> P[Start Deploy PROD met version en expected_sha]
    P --> Q[Deploy PROD verifieert tag-SHA en main-historie]
```

## Belangrijke workflows

- `.github/workflows/test.yml`: bouwt assets en draait PHPUnit-tests.
- `.github/workflows/open-pr-to-test.yml`: opent automatisch een PR van elke werkbranch naar `release/test`.
- `.github/workflows/auto-merge-test-pr.yml`: wacht op check `test` en merget de PR naar `release/test`.
- `.github/workflows/deploy-saas.yml`: deployt TEST op push naar `release/test`.
- `.github/workflows/create-prod-tag.yml`: maakt een PROD-tag op `origin/main` en start PROD-deploy.
- `.github/workflows/deploy-prod.yml`: deployt een gevalideerde `v*` tag naar PROD.
