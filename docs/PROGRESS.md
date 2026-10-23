# Delivery progress

## Overall status — application still in development

Foundation and core tenant workflows are implemented. All 24 module manifests are registered; these manifests are not complete business modules. The ledger and business workflows in phases 2–10 remain pending. This checkout is not yet a production accounting system.

## Current core verification

- Company onboarding, presets, headquarters creation, memberships and tenant-scoped Spatie/Shield roles.
- Branch access, branch creation/switching, top-bar branch/language selectors and company settings.
- Company/branch module state, dependency and core-disable guards, settings preservation, audited toggles and generic job/listener runtime gating.
- Private scanned attachments, signed downloads, queued CSV imports with row errors and duplicate-job protection, formula-safe CSV export.
- Immutable audit-event models, company-scoped master-data auditing via the installed package, HMAC audit chain and integrity verification screen.
- Transactional per-branch document numbering and localization-pack defaults.
- 74 Pest tests passing on SQLite; the earlier 73-test core suite also passed on isolated MariaDB. Pint and Larastan level 6 checked during development; final phase checks recorded below as they run.
- `modules:doctor` validates all 24 manifest keys, dependency graph and English/Bangla names. Module runtime tests cover all 22 optional manifests; financial/business feature suites remain future work.
- Browser checked onboarding, branch creation, module cards, team screen and top-bar selectors. Profiled modules/team screens each executed 22 queries in approximately 26/28 ms, with zero new slow-query or N+1 suggestions after query-fingerprint and permission-cache fixes. These are local core-screen measurements, not the release benchmark.

## Remaining scope

Accounting engine, contacts, banking, expenses, trade, stock, POS, people, manufacturing, assets/projects/growth, messaging, AI business workflows, API and release hardening remain incomplete. Account-mapping wizards and module-specific open-item guards must be completed as their owning domain modules are implemented. No claim is made that all 24 modules or the final definition of done is complete.

## Phase 0 implementation

- Laravel 12.70.1, Filament 3.3.57, and all seven requested packages installed with composer.lock; Composer resolves for PHP 8.3.
- Separate Filament `/app` and package Console `/admin`, one users table/web guard, shared login, application-enforced permission checks.
- Six integration contracts with production bindings and explicit test doubles; slug integration verified against real package generation.
- Protected AI studios; disabled unsafe public package API/device ingress and automatic AI navigation until tenant-aware module gates exist.
- Console host-view override removes package sample financial data; actual user/failed-job counts only. Host-owned profiler summary reads real package tables.
- AI suggestions use structured output, validation and basic email/numeric identifier redaction. Contract exposes no posting/payment/approval operations.
- Permission-only seeding, interactive strong-password owner installer, foundation health command, explicit Noto font registrations, English/Bangla host-view labels.
- PHP 8.3 container definition, MySQL/Redis Compose services, CI configuration, operational instructions and package discrepancies recorded.

## Verification observed

| Check | Result |
| --- | --- |
| Pest, SQLite | **24 passed**, 109 assertions |
| Fresh migrations and seed, SQLite | Passed, all 22 migrations |
| Fresh migrations and seed, isolated MariaDB 11.8.6 | Passed, all 22 migrations |
| Pest, isolated MariaDB | Earlier 19-test suite passed, 103 assertions; five added tests subsequently verified on SQLite |
| Pint | Passed |
| Larastan level 6 | Passed using serial `--debug` because sandbox blocks its parallel TCP listener |
| Composer validate --strict | Passed |
| Composer audit --locked | No security advisories |
| Route cache compilation and clear | Passed |
| Route method/domain/URI uniqueness | Covered by passing test |
| Shared-session Console/Filament HTTP render | Passed; unauthorized users and guests denied |
| Native PDF adapter | Real PDF header and package-fake recording verified |
| Bangla, Arabic and CJK slug generation | Passed |
| Font installation | Noto Sans, Noto Sans Bengali and Noto Sans Arabic downloaded into storage |
| app:doctor | Foundation checks passed |
| app:doctor --production on host defaults | Correctly fails: GD missing; default DB is SQLite; Redis network access blocked inside sandbox |
| Redis outside sandbox | PONG |
| Exact PHP 8.3/MySQL 8 CI | Configured; execution not observed |

Temporary verification MariaDB was isolated from existing databases and shut down after verification. No existing database credentials or data were used.

## Phase 0 gate still open

1. Host GD extension is missing; target PHP 8.3 container build is not verified.
2. Drutogoti lacks advertised dashboard, sampling/path exclusion, cache/cookie capture and retention hooks. Minimal host summary works, but capture remains off pending a privacy-safe tested workaround. No performance/N+1 acceptance claim is made.
3. Browser-level panel visual coexistence, click-through and profiler review have not been performed. HTTP/session coexistence is proven only.
4. Adapters expose foundation operations; full per-company settings, AI budgets/redaction and module gates depend on later domain implementation. Protected studios are reachable but business integrations are not complete.
5. No screenshot/golden-file proof of multilingual document shaping yet. Font installation and native PDF generation do not prove every document renders correctly.

Per the supplied phase rule, do not mark Phase 0 complete or progress to Phase 1 until its remaining gate requirements pass. A foundation checkpoint commit is not a completed-phase commit.

## Remaining delivery

Phase 1: core, companies/branches, module manifests/manager/gates/presets, scopes, numbering, attachments/audit/import infrastructure.

Phase 2: immutable ledger, posting rules, account mapping, fiscal periods, FX/tax, reports/snapshots and all accounting invariants.

Phases 3–10: money/parties, trade, stock/catalog, POS, people/payroll/attendance, growth/assets/manufacturing, intelligence/API/messaging, then security/performance/release hardening. Full detail remains in REQUIREMENTS.md sections 15–16. No release definition-of-done item is certified complete.

## Continued foundation verification

- Host-owned RequestCapture now enforces sampling/path exclusions, clears state for each request, redacts SQL values/bindings and hashes cache keys; real package job/analyzer persist results. Tests cover capture and zero-sample/exclusion behavior. Suite: 26 passed, 118 assertions; Pint and level-6 analysis pass.
- Matching GD binary downloaded from Ubuntu registry and loaded successfully from /tmp without changing system configuration.
- Browser login, Filament, Console, AI Hub Studio and Catalog Studio checked. Both layouts render independently with shared session auth.
- Browser profiler review exposed a false duplicate pattern caused by redacting SQLite identifiers. Added a canonical SQL fingerprint to distinguish distinct statements while storing redacted SQL. No vendor changes.
- Foundation contracts and safety boundary are ready for core module implementation. Per-business integrations and full document golden tests remain assigned to their owning phases.

## Core checkpoint gate

77 tests / 490 assertions passed on SQLite and fresh isolated MariaDB. Pint passed, Larastan level 6 passed, manifest doctor passed and route caching passed. Company settings and audit timestamp-tampering coverage are included. Core screen proof is saved in docs/screenshots/core-branches.png. Ledger implementation is next; business workflows remain unimplemented.
