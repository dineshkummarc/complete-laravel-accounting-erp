# MASTER PROMPT — `laravel-accounting`
### Complete modular "Everything" accounting & business application · Laravel 12 · Filament 3 · Multi-branch

> Paste this whole document to the AI coder as the task. It is self-contained. Build the **complete, working application** — not a skeleton, not a demo, no placeholders, no `TODO`, no mock data in production code.

---

## 0. ROLE, RULES OF ENGAGEMENT, AND HOW TO WORK

You are a senior Laravel architect and the sole engineer building **laravel-accounting**: one application that can act as an accounting system, an ERP, a POS, an HR/payroll system — **where each business switches ON only the modules it needs and switches the rest OFF**. It must support **multiple branches** from day one.

**Hard rules**
1. **Do not ask questions.** When something is ambiguous, pick the most reasonable option, write it in `docs/DECISIONS.md` (date, decision, reason), and continue.
2. **Never invent package APIs.** After installing each of the seven `imrandevbd/*` packages (section 3), read its `README.md`, `composer.json`, config file, service provider and migrations in `vendor/` and code against what is really there. If README and code disagree, **the code wins**; log the mismatch in `docs/PACKAGE-NOTES.md`.
3. **Never edit `vendor/`.** If a package has a bug, write a failing test, log it in `docs/PACKAGE-NOTES.md`, and work around it inside the adapter layer (section 3.0).
4. Work in **phases** (section 15). Each phase ends with: migrations run clean from scratch, tests green, Pint clean, PHPStan/Larastan clean, `docs/PROGRESS.md` updated, one git commit. Do not start the next phase until the gate passes.
5. **Money is never a float.** Use `decimal(20,4)` columns + `brick/money` / `brick/math` value objects. Rounding rules live in one place.
6. **Posted accounting records are immutable.** Correct by reversal, never by edit/delete.
7. Code style: `declare(strict_types=1)`, PHP 8.3 enums, readonly DTOs, Action/Service classes (one public `handle()`/`execute()`), Form-free business logic (Filament resources only call Actions), Policies for every model, Events for cross-module effects, no fat controllers/resources.
8. Everything user-facing is translatable (`en`, `bn` shipped; RTL-ready so `ar`/`ur` can be added). No hard-coded strings in Blade/PHP UI code.
9. Every feature ships with tests (Pest). A module is not "done" until the *module-off* tests in section 4.6 pass too.
10. Final answer of the whole job = working repo + `README.md`, `.env.example`, `docs/INSTALL.md`, `docs/MODULES.md`, `docs/MODULE-AUTHORING.md`, `docs/DECISIONS.md`, `docs/PACKAGE-NOTES.md`, `docs/PROGRESS.md`.

---

## 1. PRODUCT VISION

One Laravel application, one codebase, one database schema, that adapts to the business:

- A **tiny shop** turns on: Accounting + Contacts + Sales + Inventory + POS.
- A **trading/wholesale company** adds: Purchases, Banking, Collections, Tax, Multi-branch warehouses.
- A **factory** adds: Manufacturing, Fixed Assets, Payroll, Attendance, Budgeting.
- A **service business/agency** uses: Accounting + Contacts + Sales + Projects/Timesheets + Expenses + Payroll.

Switching a module OFF hides it everywhere (navigation, routes, API, widgets, scheduled jobs, listeners, reports) but **never deletes data**. Switching it ON again restores everything.

Primary market: Bangladesh (BDT, VAT/Mushak, TDS, bKash/Nagad/Rocket wallets, Bangla numerals, ZKTeco/eSSL attendance devices), but **globally configurable** (currency, tax, locale, fiscal year, COA template). Clients are in Bangladesh, Australia and the USA, so nothing may be hard-wired to one country — country specifics live in a **Localization Pack** (`bd` pack shipped; `generic` pack shipped).

---

## 2. LOCKED TECH STACK (derived from the seven packages' real `composer.json` constraints)

| Item | Decision | Why |
|---|---|---|
| PHP | **8.3** (8.4 OK) | `drutogoti` requires `^8.3` |
| Laravel | **12.x** (NOT 13) | `drutogoti` ⇒ `^11\|^12`; `laravel-attendance-hub` ⇒ `^10\|^11\|^12` |
| Admin UI | **Filament v3 (3.x)** | `laravel-attendance-hub` ships a **Filament v3** plugin; `laravel-ai-hub`/`laravel-ai-catalog` plugins support Filament v3 |
| DB | **MySQL 8** primary (MariaDB 10.6+ ok). Keep SQL portable; no vendor-only features in domain code | user's production stack |
| Cache / Queue / Lock | **Redis** (`predis` or `phpredis`); Horizon optional | drutogoti queue offload, slug atomic locks, module cache |
| Frontend | Filament (Livewire 3 + Alpine + Tailwind). POS screen = custom Livewire page. No SPA | |
| Auth | Laravel session auth + Sanctum for API | |
| RBAC | `spatie/laravel-permission` (teams = company) + `bezhanSalleh/filament-shield` | |
| Audit | `owen-it/laravel-auditing` for config/master data **plus** the immutable accounting journal | |
| Excel/CSV | `openspout/openspout` or `maatwebsite/excel` | |
| Money | `brick/money`, `brick/math` | |
| Testing | Pest 3, `orchestra/testbench` not needed (app-level), Larastan level ≥ 6, Pint | |
| PHP ext | `mbstring intl gd bcmath sockets redis pdo_mysql` (**sockets is required** for ZKTeco devices) | |

**Composer note (important):** the seven packages are published as **`dev-main`** (only `laravel-ai-hub` has a tag, `v1.6.0`). Set in the app's `composer.json`:

```json
"minimum-stability": "dev",
"prefer-stable": true
```

Commit `composer.lock`. Never run a blanket `composer update` — update the `imrandevbd/*` packages one at a time and re-run the integration smoke tests.

---

## 3. THE SEVEN PACKAGES — WHAT, WHERE, HOW

### 3.0 Integration architecture (mandatory)

These packages are young (low install counts, `dev-main`). Therefore **never call them directly from domain code**. Create an anti-corruption layer:

```
app/Integrations/
  Speed/        DrutogotiAdapter        (config + gating only)
  Console/      AdminPanelAdapter       (panel registration + auth bridge)
  Catalog/      CatalogService          (wraps AiCatalog)
  Ai/           AiGateway               (wraps AIHub; ALL AI calls go through it)
  Attendance/   AttendanceGateway       (wraps AttendanceHub)
  Pdf/          PdfRenderer             (wraps UnicodePdf)
  Slug/         (traits/config only — see 3.7)
```

Each adapter: one interface in `Contracts/`, one real implementation, one fake implementation for tests, and a smoke test proving the real package boots. Domain code depends on the interface only.

**Route/path map (avoid collisions):**

| Path | Owner |
|---|---|
| `/app` | Filament panel `app` — the day-to-day business UI (all modules) |
| `/admin` | `laravel-admin-panel` — Owner/Platform **Console** (section 3.2) |
| `/ai-hub` | AI Hub Studio (restricted to super admins) |
| `/ai-catalog` | AI Catalog Studio (restricted) |
| `/drutogoti` | Performance dashboard (restricted) |
| `/iclock/cdata` (GET/POST) | ADMS push from attendance devices (public, device-authenticated) |
| `/api/attendance/*` | attendance webhook-bridge + virtual check-in |
| `/api/v1/*` | our own REST API (module `api`) |
| `/i/{token}` | public invoice/receipt view (unguessable token, **not** a slug) |

### 3.1 `imrandevbd/drutogoti` — speed (performance profiler & fixer)
- Namespace config: `config/drutogoti.php`. Requires PHP ^8.3, Laravel 11|12.
- Install: `composer require imrandevbd/drutogoti` → `php artisan vendor:publish --tag="drutogoti-config"` → `php artisan vendor:publish --tag="drutogoti-migrations"` → `php artisan migrate`.
- What it does: N+1 detection, slow-query capture (with backtrace), cache hit/miss telemetry, cookie/header bloat, image-asset scanner, suggestion engine, queued ingestion (zero request overhead), dashboard at `/drutogoti`.
- **How to use it here**
  - `.env`: `DRUTOGOTI_ENABLED=true` in local/staging, **sampled in production** (`DRUTOGOTI_SAMPLE_RATE=0.1`), `DRUTOGOTI_STORAGE_DRIVER=queue`, `DRUTOGOTI_QUEUE_CONNECTION=redis`, `DRUTOGOTI_QUEUE_NAME=low`. **Disabled in the test suite.**
  - Dashboard middleware: `['web','auth','can:platform.console']` (super admins only). Add `/app/*` Livewire update paths you find noisy to `exclude_paths`.
  - Retention: keep 7 days, `auto_prune=true`.
  - **Make it part of the workflow:** before each phase gate, run the app seeded with demo data, click through every screen of that phase, open `/drutogoti`, and **fix every N+1 and slow query it reports** (eager loading, indexes, caching). Record the before/after in `docs/PROGRESS.md`. A phase with unresolved N+1 on its list/detail screens does not pass.
  - Expose its health summary as one widget in the Console (3.2).
  - Do **not** rely on its roadmap items (Slack alerts, PR checks) — they are not shipped.

### 3.2 `imrandevbd/laravel-admin-panel` — dashboard / Owner & Platform Console
- Namespace `ImranDevBD\LaravelAdminPanel\…`. PHP ^8.2, Laravel 11–13. **Zero companion deps, ships precompiled assets (no Node needed in the host app).** It is a *Filament alternative* with its own fluent `Resource`, `Panel`, widgets, RBAC, audit log, media library, health center, log viewer, queue inspector, settings store, headless OpenAPI layer, tenancy, RTL.
- Install: `composer require imrandevbd/laravel-admin-panel` → `php artisan admin-panel:install` (publishes `config/admin-panel.php`, assets to `public/vendor/admin-panel`, runs migrations, creates the first super-admin). Useful: `admin-panel:doctor`, `admin-panel:make-widget`, `admin-panel:make-page`, `admin-panel:make-panel`.
- **Decision (record in DECISIONS.md): two panels, clear split, one `users` table.**
  - **Filament `/app`** = everything a business user does all day (all module resources, POS, reports).
  - **admin-panel `/admin`** = the **Console**: executive dashboard (12-column widget grid with sparklines: revenue, expenses, cash position, receivables/payables, stock value, branch comparison, top customers/products — reading from the same reporting services Filament uses), company/branch overview, **module manager overview**, system health (OPcache, queue, disk, DB latency), log viewer, queue/failed-job inspector, tamper-evident audit viewer, settings store, drutogoti summary, AI spend summary (from AI Hub).
  - Access to `/admin` requires permission `platform.console`; bridge to the app's `users`/`spatie` permissions through the `AdminPanelAdapter` (read its auth/guard options from source — do not run a second user table or a second RBAC as source of truth).
- **Phase-0 spike (mandatory, before anything depends on it):** prove Filament and admin-panel coexist — separate layouts, no Alpine/Tailwind clash, no route-prefix clash, shared auth works, `php artisan route:list` clean. If a hard conflict appears, keep admin-panel on its own subdomain/route group with its own layout and note it in DECISIONS.md. **Never mix their Blade layouts.**
- If the admin-panel API for a needed widget is missing, build the widget as a plain Blade widget it supports (`admin-panel:make-widget`), not a hack.

### 3.3 `imrandevbd/laravel-ai-catalog` — scrape/generate catalog data from online & other sources
- Namespace `ImranDevBd\AiCatalog\…`, facade `AiCatalog`. PHP ^8.2, Laravel 10–13. **Requires `imrandevbd/laravel-ai-hub ^1.6`.** Suggests Filament (for the "Generate with AI" action + review resource).
- Install: `composer require imrandevbd/laravel-ai-catalog` → `php artisan vendor:publish --tag=ai-catalog-config` → `php artisan migrate`. Studio at `/ai-catalog`.
- Input modes: keyword, raw text, **photo (vision)**, **competitor/supplier URL (scrape + rewrite)**, bulk CSV/JSON, voice, datasheet (pdf/md/txt/csv/html). Output: title, SEO meta, short/long description, specs, tags, category (**matched strictly to our real categories — never invented**), FAQ, SKU/GTIN/MPN grounding, JSON-LD, translations (20 locales), alt-text, image quality + duplicate detection. Workflow: `pending → generating → draft → in_review → approved → published | rejected | failed`. **Only `approve` writes to our real table.** Commands: `ai-catalog:generate`, `ai-catalog:bulk`, `ai-catalog:audit`, `ai-catalog:refresh-stale`, `ai-catalog:retry-failed`, `ai-catalog:inspect-image`, `ai-catalog:export-feed`, `ai-catalog:agentic-feed`.
- **How to use it here (Inventory + Catalog-web modules)**
  - `config/ai-catalog.php` → `schema.model = App\Modules\Inventory\Models\Item`, map: `title→name`, `short_description→short_description`, `long_description→description`, `specs→specifications` (json), `category→category_id`, `json_ld→structured_data`, `alt_text→image_alt`. `on_approve = update_or_create`. `taxonomy.model = App\Modules\Inventory\Models\ItemCategory`, `min_score ≥ 60`.
  - Wire the Filament plugin: `AiCatalogPlugin::make()` in the `app` panel **gated by the `ai-assistant` module** and add `GenerateWithAiAction` on the Item resource ("Generate with AI") and on a bulk-import wizard.
  - Scrape-from-online use case: "Add item from supplier URL" (uses `fromUrl`). **Safeguards:** respect robots/ToS note in UI, rate-limit per company, store source URL on the draft, never auto-approve, human review queue is the Inventory manager's inbox.
  - **Turn OFF the commerce extras that an accounting app doesn't need** (DPP, social scripts, spatial/3D, return-reduction) by default; keep them available behind a per-company setting under module `catalog-web`. This controls token cost.
  - Set tenancy resolver to the current **company**; set a monthly **budget cap** per company (`block` for trial plans, `warn` otherwise).
  - Run `ai-catalog:audit` weekly via the scheduler (per enabled company) and surface the health score on the Inventory dashboard.

### 3.4 `imrandevbd/laravel-ai-hub` — use any AI
- Namespace `ImranDevBd\AiHub\…`, facade `AIHub`. PHP ^8.2, Laravel 10–13. 13 providers (OpenAI, Gemini, Claude, Grok, DeepSeek, Mistral, Groq, Ollama, OpenRouter, Azure, Together, Fireworks, Perplexity), failover chain, retries, **cost tracking in USD**, budgets (monthly/provider/job; `block`|`warn`), structured JSON-schema outputs (`AIHub::structured($schema)` / `asJsonSchema`), vision (`->image()`), tools, response cache, templates, SSE streaming, chat sessions (`AIHub::chat()`), thinking/reasoning controls, `AIHub::fake()` testing, zero-worker logging (`after_response`), Studio at `/ai-hub`, optional Filament plugin `AiHubPlugin`.
- Install: `composer require imrandevbd/laravel-ai-hub` → `php artisan vendor:publish --tag=ai-hub-config` → `php artisan migrate`. Keys can live in `.env` or in Studio (stored encrypted).
- **How to use it here — all through `AiGateway`:**
  - Tag every call: `->forJob('accounting.bill-ocr')`, `'accounting.categorize-bank-line'`, `'accounting.report-narrative'`, `'accounting.collection-message'`, `'accounting.anomaly-scan'`, `'catalog.*'`.
  - Always use **structured JSON schemas** and validate output with Laravel validation before use.
  - **Hard safety rules:** AI **never posts a journal, never approves, never pays**. It only creates **drafts/suggestions** that a human confirms. Redact PII (phones, national IDs, bank numbers) before prompts. No raw-SQL generation: "ask your data" maps the question to a **whitelisted set of report definitions + parameters** (section 10), not SQL.
  - Features (module `ai-assistant`): (1) bill/receipt/invoice **OCR → draft purchase bill or expense**; (2) **bank-statement line categorization** suggestions with confidence; (3) **anomaly scan** (duplicate bills, round-number spikes, weekend postings, unusual vendor amounts); (4) **narrative** for P&L/cash-flow ("what changed this month"); (5) **collection reminder drafts** in Bangla/English; (6) conversational "Ask your books" over whitelisted reports; (7) item data entry (via AI Catalog).
  - Auth for `/ai-hub`: `AIHub::auth(fn ($r) => $r->user()?->can('platform.console'))`. Set `AI_HUB_FILAMENT_NEW_TAB=true`.
  - Per-company spend caps (budget by `forJob` prefix) + a "AI usage & cost" widget (from `AIHub::analytics()`).
  - Schedule `model:prune` for `AiRequestLog` daily.
  - All AI tests use `AIHub::fake()`; **no network in tests.**

### 3.5 `imrandevbd/laravel-attendance-hub` — any attendance machine
- Namespace `ImranDevBd\AttendanceHub\…`, facade `AttendanceHub`. PHP ^8.2, Laravel 10–12, **needs `ext-sockets`** for ZKTeco native TCP. Filament **v3** plugin `AttendanceHubPlugin`.
- One driver interface for ZKTeco, Hikvision, Anviz, FingerTec, eSSL, VIRDI, Suprema, Deli, Granding, Soyal, generic **webhook bridge** (ESP32/RPi Wiegand), **virtual** (mobile GPS geofence / QR), and a **fake** driver. Features: LAN pull, **ADMS cloud push** (`/iclock/cdata`), live capture, auto-detect (`AttendanceHub::autoDetect($ip)`), subnet discovery, model→driver routing (`AttendanceHub::model('UA860')`), punch pipeline (debounce 2 min, anti-passback), SHA-256 dedupe, **`AttendanceCalculator`** (late/early/overtime/half-day/status), biometric **template vault** (encrypted at rest), HMAC-signed outgoing webhooks, multi-tenancy scoping, `attendance:simulate`.
- Install: `composer require imrandevbd/laravel-attendance-hub` → `php artisan vendor:publish --tag="attendance-hub-migrations"` → `php artisan vendor:publish --tag="attendance-hub-config"` → `php artisan migrate`. Tables: `attendance_devices`, `attendance_logs`, `device_user_maps`, `device_biometric_templates`. Run `php artisan attendance:catalog` to see the **actually supported providers/models** (README's config comment and brand table differ — trust the command/code).
- **How to use it here (modules `hr` + `attendance`)**
  - Tenancy: `tenancy.enabled = true`, `tenant_resolver` = resolves **company** from (a) Filament tenant, else (b) `auth()->user()->current_company_id`, else (c) **device serial / bridge token → company** for non-HTTP-user contexts (ADMS push and webhook bridge have no logged-in user — implement this lookup).
  - **Branch mapping:** do **not** alter the package's tables. Create our own `attendance_device_branch` (`device_id`, `branch_id`) and our own `hr_attendance_days` summary table. Every device must be assigned to a branch; employees are matched via `device_user_maps` → `hr_employees.id`.
  - Listen to the package's `AttendanceRecorded` event → update that employee's `hr_attendance_days` row (first-in, last-out, status, late/OT minutes via `AttendanceCalculator` with the employee's assigned **shift**). Nightly job recomputes yesterday for safety. **Payroll reads only `hr_attendance_days`**, never raw logs.
  - Scheduler (only for companies with module `attendance` ON): `attendance:sync --all --queue` every 15 min, `attendance:health` hourly. Supervisor worker config + nginx `location /iclock/` snippet (`client_max_body_size 10M`) go into `docs/INSTALL.md`.
  - Manual correction screen (HR) with reason + audit; corrections never delete raw logs.
  - Mobile/virtual check-in page using the `virtual` driver with per-branch geofence (lat/lng/radius) taken from the **branch** record.
  - Biometric data is special-category: keep package encryption defaults, never log templates, never export them, restrict the vault screens to permission `attendance.biometrics`.
  - Tests use `AttendanceHub::fake()` and `attendance:simulate`.

### 3.6 `imrandevbd/laravel-unicode-pdf` — PDF in any language
- Namespace **`ImranDev\UnicodePdf\…`** (note: `ImranDev`, not `ImranDevBd`), facade `UnicodePdf`. PHP 8.1–8.5, Laravel 10–13. **Bundled `native` engine, zero extra PDF libs** (optional dompdf/mpdf/tcpdf/browsershot). RTL/BiDi, complex-script shaping (Bengali conjuncts, Arabic joining, Indic), font fallback, 20 presets (`bengali`, `arabic`, `urdu`, `hindi`/`indian`, `cjk`, `universal`, …), native numerals (`NumeralConverter`, `@unicodeNumerals`), named **profiles** (`invoice`, `receipt`, `rtl-report`), `Pdfable`/`GeneratesPdf` model trait, queue/cache/store/mail-attachment helpers, events, `UnicodePdf::fake()`.
- Install: `composer require imrandevbd/laravel-unicode-pdf` → `php artisan vendor:publish --tag=unicode-pdf-config` (+ `--tag=unicode-pdf-views`, `--tag=unicode-pdf-fonts` if needed) → `php artisan unicode-pdf:font:install --font=bengali` (also `arabic`, `universal`) → `php artisan unicode-pdf:diagnose`.
- **How to use it here — `PdfRenderer` adapter is the only caller.** Every printable document renders through it: invoices, credit/debit notes, quotations, receipts, delivery notes, purchase orders, payslips, cheques/vouchers, journal vouchers, statements, **all reports**, barcode label sheets, attendance sheets.
  - Per-company document language and numeral system (`en`/`bn` digits) setting; company can pick the preset (`bengali`, `universal`, `arabic`, …). Mixed-language documents (English item names + Bangla notes) use the `universal` preset.
  - **Respect the native engine's CSS support**: use `<table>`-based layouts, `font-size`, `color`, `background-color`, `text-align`, margins/padding, `<hr>`, lists, `<img>` (local path or `data:` URI), `page-break-before/after`. **Do not use flexbox/grid** in PDF templates. Table rows auto-paginate. For QR codes, generate PNG → `data:` URI image (e.g. `endroid/qr-code`).
  - Per-company templates: logo, header/footer (`->header()/->footer()`), page numbers (`{PAGE_NUM} / {PAGE_COUNT}`), watermark (`DRAFT`/`PAID`/`VOID` via `->when(...)`), Unicode filenames (`চালান-1001.pdf`).
  - Large reports/batches: `->queue()` + notification when ready. Cache stable documents with `->cache()`.
  - Enable `security.allow_remote_images=false`. Keep `allowed_local_paths` to storage/public.
  - Add a **"PDF font health" check** to the Console and to `app:doctor`.
  - Tests: `UnicodePdf::fake()` + a golden-file test per document type that renders Bangla, Arabic and English samples without tofu/`???` (use `checkGlyphs`).

### 3.7 `imrandevbd/laravel-universal-slug` — slug in any language
- Namespace **`ImranDev\UniversalSlug\…`**, facade `UniversalSlug`. PHP 8.1–8.5, Laravel 10–13. Keeps native scripts by default (Bengali/Arabic/Hindi/CJK/Cyrillic…), modes `preserve_unicode | transliterate | ascii_only`, zero-space URLs, Eloquent trait `HasUniversalSlug` + `#[Slug(...)]` attribute, unique-per-scope (`uniqueScope`), locale-scoped uniqueness, collision strategies, reserved words (`admin`, `api`, `login`, `dashboard`, `settings`, `users`, …), `HasSlugHistory` with automatic **301 redirects**, events `SlugGenerated`/`SlugChanged`, validation rules (`Rule::universalSlug()`, `Rule::uniqueUniversalSlug(Model::class)`), commands `slug:generate|regenerate|prune|audit`, atomic cache lock.
- Install: `composer require imrandevbd/laravel-universal-slug` → `php artisan vendor:publish --tag="universal-slug-config"` and `--tag="universal-slug-migrations"` → `php artisan migrate`.
- **How to use it here**
  - Models with slugs: `Company`, `Branch`, `Item`, `ItemCategory`, `Brand`, `Contact` (for public statement pages only if enabled), `Project`, `CatalogPage` (module `catalog-web`), `Department`. Use `uniqueScope(fn ($q,$m)=>$q->where('company_id',$m->company_id))`; `Item`/`ItemCategory`/`CatalogPage` use `HasSlugHistory` + route binding `{item:slug}` so renamed products 301 properly (SEO).
  - `usingLanguage($company->locale)`, `->protectReserved(true)`, `->includeTrashed(true)`.
  - **Do NOT use slugs for**: invoice/journal/document numbers (use the gap-free **number sequences**, section 7.5), nor for public invoice links (use random unguessable tokens). Slugs are for human-readable URLs and codes of master data only.
  - Add `slug:audit` for each slugged model to `app:doctor`.
  - Console gets a "Slug health" line from `slug:audit --json`.

---

## 4. THE MODULE SYSTEM (the heart of the request — design it first)

### 4.1 Concept
- **Company** (tenant) → has many **Branches**. A single-business install has exactly one company; the same code supports many companies (SaaS-ready). Filament **tenancy** is by Company.
- A **Module** is a self-contained unit under `app/Modules/<Name>/` that can be switched ON/OFF **per company**, and optionally **per branch** (sparse override: a branch may *disable* a module the company enabled, e.g. "Manufacturing only at the Factory branch"; a branch can never enable what the company hasn't).
- **All migrations always run** (every module's tables always exist). Toggling is a **runtime** concept, not migrate/rollback. Data is never dropped on disable.

### 4.2 Required structure per module
```
app/Modules/Sales/
  SalesModule.php                 // extends BaseModule (the manifest)
  SalesServiceProvider.php
  Models/ Actions/ Services/ Enums/ DTOs/ Events/ Listeners/ Policies/
  Filament/Resources|Pages|Widgets/   // each uses HasModuleGate
  Posting/                        // PostingRule classes (ledger integration)
  Reports/                        // ReportDefinition classes
  Http/ (api controllers, public routes)
  database/migrations|seeders|factories
  lang/en|bn
  routes/web.php api.php
  tests/
```

`BaseModule` manifest exposes: `key()`, `name()`, `description()`, `group()`, `icon()`, `version()`, `dependsOn(): array`, `optionalIntegrations(): array`, `conflictsWith(): array`, `defaultEnabled(): bool`, `isCore(): bool`, `permissions(): array`, `settingsSchema(): array` (Filament form schema for the module's settings), `requiredAccountMappings(): array`, `scheduledTasks(Schedule $s, Company $c)`, `dashboardWidgets()`, `reportDefinitions()`, `searchProviders()`, `enableChecklist(Company $c): array`, `onEnable(Company $c)`, `onDisable(Company $c)` (pre-flight), `seedCompanyDefaults(Company $c)`.

### 4.3 Storage
- `company_modules`: `company_id`, `module_key`, `enabled` bool, `settings` json, `enabled_at`, `enabled_by`, `disabled_at`, `disabled_by`. Unique(`company_id`,`module_key`).
- `branch_modules`: `branch_id`, `module_key`, `enabled` bool (override). Sparse.
- Registry of available modules lives **in code** (discovered from service providers), not in the DB.
- `module_events` audit table (who toggled what, when, why).

### 4.4 `ModuleManager` service (single source of truth)
`isEnabled(string $key, ?Company $c = null, ?Branch $b = null): bool`, `enabledKeys(...)`, `enable()`, `disable()`, `dependentsOf()`, `dependenciesOf()`, `canDisable()` (returns reasons), `preset()`. Caches per company in Redis with tags; invalidated on toggle. **Core modules (`core`, `accounting`) can never be disabled.**

### 4.5 What "OFF" must do (enforce every one)
1. **Navigation** hidden (`shouldRegisterNavigation()`), **global search** excludes, **resources/pages/widgets** `canAccess()` false — implement as trait `HasModuleGate` (reads `static $module = 'sales'`) used by *every* Filament class in a module.
2. **HTTP routes & API**: route middleware `module:sales` → 404 (web) / 403 JSON (api).
3. **Policies**: `ModuleGate::allows('sales')` checked inside policy base class.
4. **Scheduler**: per-company task loops skip disabled modules.
5. **Listeners/Posting rules**: module event listeners no-op when OFF (but data events from **other** modules must still work).
6. **Reports & dashboard widgets** of the module disappear; cross-module reports omit that module's columns gracefully (no errors, no zero-fakes).
7. **Settings, notifications, import/export templates** of the module hidden.
8. **Blade/Livewire**: `@module('pos') … @endmodule` directive and `Module::enabled('pos')` helper.
9. **Disable guardrails (`canDisable`)**: block if an **enabled module depends on it** (show the list; offer "disable these too"); warn/block if there are **open items** (POS: open shifts; Sales: unposted drafts older than N days → offer archive wizard; Inventory: non-zero stock → require explicit confirmation text; Payroll: unpaid payslips; Attendance: devices still syncing → stops sync). Provide `--force` only in CLI with reason logged.
10. **Graceful degradation matrix**: e.g. Sales without Inventory ⇒ lines are free-text/service items and no stock moves; Purchases without Inventory ⇒ expense-type lines; Payroll without Attendance ⇒ manual days/overtime entry; Sales without POS ⇒ no shift/register columns. Document each integration in `docs/MODULES.md`.

### 4.6 Mandatory module-toggle test suite (Pest, per module)
- ON → routes work, nav shows, widgets show, jobs run.
- OFF → routes 404/403, nav hidden, widgets hidden, jobs skipped, no exceptions in other modules' screens/reports.
- Dependency block: disabling a module that another enabled module needs is refused with reasons.
- Branch override: disabled for Branch B only ⇒ B users can't see it; Branch A unaffected; consolidated reports still include B's historical data.
- Re-enable restores prior data and settings.

### 4.7 UX for toggling
- Filament page **Settings → Modules** (company owner): grouped cards (Sales & Purchasing, Stock & Manufacturing, Money, People, Growth, Intelligence), each with toggle, dependency chips, "what you get" bullets, **enable wizard** (shows `enableChecklist`: required account mappings, default seed, numbering series, permissions) and a **disable confirmation** showing consequences.
- **Business presets** at onboarding (applies a module set, COA template, tax pack, numbering, sample roles). Editable afterwards:

| Preset | Modules ON |
|---|---|
| Retail shop | core, accounting, contacts, sales, inventory, pos, banking, expenses, tax |
| Wholesale / distribution | + purchases, collections, messaging, multi-warehouse inventory, budgeting |
| Manufacturing | + manufacturing, fixed-assets, hr, attendance, payroll, budgeting |
| Service / agency | core, accounting, contacts, sales, projects, expenses, hr, payroll, banking, tax |
| Restaurant / café | core, accounting, contacts, sales, inventory, pos, purchases, expenses, hr, attendance, payroll |
| Freelancer / micro | core, accounting, contacts, sales, expenses, banking |
| Custom | owner picks |

- CLI: `php artisan modules:list`, `modules:enable {company} {key}`, `modules:disable {company} {key}`, `modules:doctor` (checks manifests, deps cycles, missing permissions/translations/tests), `modules:make {Name}` (scaffold).

---

## 5. MULTI-BRANCH DESIGN (first-class, not an afterthought)

- Tables: `companies`, `branches` (`company_id`, `code`, `name`, `slug`, `type` [`head_office|branch|warehouse|outlet|factory`], `parent_branch_id` nullable, address, phone, timezone, currency override nullable, geo lat/lng/radius for geofence, `is_active`, `is_default`).
- `user_branch_access` (`user_id`, `branch_id`, `is_default`); spatie roles are per **company** (teams); a user may hold different roles per branch via `branch_role_assignments` if needed (decide & document).
- **`BelongsToBranch` trait + `BranchScope`**: all transactional tables carry `company_id` **and** `branch_id`. Scope reads the **current branch context**: a specific branch, or "All my permitted branches" (consolidated) — never beyond the user's access. Writes always stamp `branch_id` (user must be in a branch context to create docs; consolidated mode is read-only for transactional create).
- **Branch switcher** in the Filament top bar (Livewire component, session-backed, remembers last choice, shows "All branches" only if permitted). Dashboard/reports respect it.
- **Per-branch**: number-sequence prefixes (`INV-DHK-2026-000123`), cash registers, bank accounts (optional restriction), warehouses/locations, price lists (optional), default accounts overrides, document headers (address/phone/VAT no.), attendance devices & shifts, payroll cost allocation, POS registers, budgets.
- **Inter-branch operations** (module-aware): stock transfers (Inventory) with in-transit state; inter-branch cash/bank transfers; shared-customer sales; auto-generated **inter-branch clearing accounts** (Due-from/Due-to) so each branch balances on its own while consolidation eliminates them.
- **Consolidated reporting**: every financial report takes `branch_ids[]` (or all) + comparison mode (branch-by-column). Consolidated P&L/BS eliminates inter-branch balances. Branch comparison widget.
- **Isolation tests**: user of Branch A cannot read/write Branch B data through UI, API, global search, exports, PDFs, or queued jobs. Test each module.

---

## 6. MODULE CATALOG (build ALL of these)

Legend: **D** = depends on, **O** = optional integration, **P** = posts to ledger. Every module also provides: permissions, translations (`en`,`bn`), settings schema, demo seeder, PDF templates (via `PdfRenderer`), reports, dashboard widgets, tests (incl. toggle suite).

### Always ON
**1. `core`** — Companies, branches, users & invitations, roles/permissions (Shield), user↔branch access, company/branch settings, **number sequences** (per company/branch/doc-type/fiscal-year, gap-free via row lock), currencies & **exchange rates** (manual + optional rate-provider interface), units, attachments (polymorphic, virus-safe, size/type limits), notes/comments, tags, audit log, notifications, **import/export engine** (CSV/XLSX with mapping, dry-run, error report), localization packs (`generic`, `bd`), global search, `app:doctor`, backups hook, API tokens UI.

**2. `accounting`** — *the ledger engine (section 7)*: chart of accounts (+ templates), journals, manual journal vouchers with attachments & approval, recurring journals, opening balances wizard, fiscal years & **period locking**, cost centers, tax-rate foundation, reconciliation tools, period close/year-end close (closing entries), **auditor read-only role**, General Ledger, Trial Balance, P&L, Balance Sheet, Cash-flow (indirect), Day Book. **P**

### Optional (toggle per company/branch)
**3. `contacts`** — customers, suppliers, both, others; groups, credit limits & terms, price lists, addresses, bank details, tax IDs, statements (PDF), merge duplicates, import. *(auto-enabled as dependency)*

**4. `sales`** — D: contacts. O: inventory, pos, projects, messaging, collections. Quotations → sales orders → delivery notes → invoices → receipts/advances → **credit notes/returns**, recurring invoices, discounts (line/doc/%/amount), tax per line, multi-currency, commissions, salesperson targets, customer statements, public invoice link (`/i/{token}`) + "pay/record payment" request, e-mail/WhatsApp/SMS send (via `messaging`), PDF. **P** (AR, revenue, tax, COGS when inventory ON).

**5. `purchases`** — D: contacts. O: inventory, ai-assistant. RFQ → PO → goods receipt (GRN) → supplier bill → payment → **debit notes/returns**, landed costs, 2-/3-way match (setting), approvals, recurring bills, supplier statements, AI bill-OCR draft. **P** (AP, expense/inventory, input tax, GRNI).

**6. `inventory`** — D: contacts(optional). Items (goods/service/bundle), categories, brands, **variants** (size/color matrix), units + conversions, barcodes/QR, **batches/lots & expiry**, **serial numbers**, warehouses/locations **per branch**, stock moves ledger, **inter-branch transfers (in-transit)**, adjustments, stock counts (cycle/full) with approval, reorder rules & low-stock alerts, valuation **FIFO / weighted average** (company setting, immutable after first move), landed cost, label printing (PDF), **AI Catalog integration + slugs** (3.3, 3.7), item import. **P** (inventory asset, COGS, adjustments, transfers).

**7. `pos`** — D: sales, inventory(optional), banking(optional). Registers per branch, **shifts** (open/close, cash count, over/short posting), fast Livewire screen (barcode scan, hold/resume, customer attach, discounts with permission, split/multi tenders incl. wallets/cards), returns/exchanges, thermal receipts 58/80 mm (PDF via `PdfRenderer`), daily Z-report, cashier permissions, optional offline-tolerant queue (PWA note), loyalty hooks. **P**

**8. `banking`** — D: —. Bank, cash and **mobile-wallet accounts** (bKash/Nagad/Rocket/etc. as configurable types), deposits/withdrawals/transfers (incl. inter-branch), customer receipts & supplier payments allocation, **cheque register** (post-dated, cleared/bounced), statement CSV import + **reconciliation** with matching rules, bank charges, fund transfers approval. **P**

**9. `expenses`** — D: —. O: ai-assistant, banking. Expense claims, petty cash, categories, receipt attachments, **AI OCR to draft**, approvals, reimbursement, per-branch/cost-center allocation, recurring expenses. **P**

**10. `fixed-assets`** — Asset register, categories, acquisition (from purchase/expense/manual), depreciation (straight-line, declining, units-of-production; monthly run), revaluation, disposal/sale with gain/loss, transfer between branches, schedule & register reports. **P**

**11. `hr`** — Employees (documents, emergency contacts, bank), departments, designations, **shifts & rosters**, holidays calendar, **leave** (types, balances, approvals), onboarding/offboarding checklists, per-branch assignment, org chart. *(foundation for attendance/payroll)*

**12. `attendance`** — D: hr. Integration with `laravel-attendance-hub` exactly as 3.5: devices registry (per branch), live status, sync, logs explorer, daily summaries, shift-aware late/OT/half-day, manual corrections with audit, mobile geofence/QR check-in, device user mapping UI, template vault (restricted), simulate demo data.

**13. `payroll`** — D: hr. O: attendance, banking, expenses. Salary structures & components (earning/deduction/employer contribution), PF/provident fund, **overtime from `hr_attendance_days`**, loans/advances recovery, bonuses/festival bonus, tax (TDS/PAYE via pack), payroll runs per branch with approval, **payslips (PDF, Bangla/English, native numerals)**, bank advice sheet (CSV/PDF), journal posting by cost center, payroll reports. **P**

**14. `crm`** — O: sales, messaging. Leads, pipeline kanban, activities/tasks, follow-ups, convert lead→contact→quotation, sources/campaign tags, simple forecasting.

**15. `projects`** — D: contacts. O: sales, expenses, payroll. Projects, tasks, **timesheets**, billable rates, project budget vs actual (cost center), expense & purchase allocation, invoice from timesheets, project P&L.

**16. `manufacturing`** — D: inventory. O: hr/payroll, fixed-assets. BOMs (multi-level, co-products/by-products), work orders, routings/work centers (simple), material issue/consumption, WIP, finished goods receipt, wastage/scrap, standard vs actual costing, production reports. Branch-restrictable (e.g., Factory only). **P**

**17. `budgeting`** — Budgets by account/branch/cost center/project/period, versions, import, **budget vs actual** & variance alerts, forecast.

**18. `tax`** — Tax returns & filings on top of core tax rates: VAT/GST/sales-tax summaries, input/output reconciliation, **withholding (TDS/VDS)** certificates, **BD pack**: Mushak register views (e.g., purchase/sales registers) and return worksheets — expose as configurable report definitions, not hard-coded legal advice; tax period locking; tax audit trail.

**19. `collections`** — D: sales. O: messaging, ai-assistant. Aged receivables worklist, promise-to-pay, follow-up log & tasks, dunning rules (levels/templates), **installment/EMI plans** for credit sales ("baki" ledger), reminder scheduling, collector performance, AI-drafted reminders.

**20. `messaging`** — Gateways via interface: e-mail (SMTP), SMS (pluggable gateway driver + a generic HTTP driver), WhatsApp (pluggable), templates with variables (Bangla/English), delivery logs, opt-outs, rate limits, per-branch sender ID.

**21. `documents`** — Folders, versioned files, expiry reminders (trade license, tax certs, contracts), permissions per role/branch, link to any record.

**22. `catalog-web`** — D: inventory. Public, SEO product/category pages (universal slugs + 301 history), enquiry/"order request" → creates sales quotation, Google Shopping/Facebook feeds and `llms.txt` via AI Catalog, sitemap. Optional extras (DPP, social scripts) behind settings.

**23. `ai-assistant`** — D: —. O: expenses, purchases, banking, collections. Everything in 3.4 (OCR, categorization, anomaly, narrative, "Ask your books", drafts). Spend dashboard & caps per company.

**24. `api`** — Sanctum tokens, **REST `/api/v1`** for contacts, items, invoices, payments, journals (read), reports (read), webhooks (HMAC-signed, retry with backoff, delivery log), OpenAPI 3.1 doc page, rate limiting, per-token scopes limited to the token owner's branches.

---

## 7. ACCOUNTING ENGINE RULES (non-negotiable)

7.1 **Double entry**: `journal_entries` (header) + `journal_lines`. Entry must balance (Σdebit = Σcredit) in **base currency** — enforced in the posting service inside a DB transaction with a lock, and covered by property-style tests.
7.2 **States**: `draft → pending_approval → posted → reversed`. Posted = immutable (model guards + policy + test that tries `update`/`delete` and fails). Reversal creates a new linked entry.
7.3 **Source documents** (invoice, bill, payroll run, POS shift, depreciation run…) never write journal rows directly: each module registers **`PostingRule`** classes (`Posting/`), invoked by an `PostDocumentToLedger` action, idempotent (re-post = no duplicates; keyed by `source_type+source_id+version`).
7.4 **Account mapping**: Settings → *Account Mapping* (company default + per-branch override) for AR, AP, Sales, Sales returns, COGS, Inventory, GRNI, Tax output/input, Cash, Bank, Wallets, Payroll payable, PF payable, Depreciation, Accumulated depreciation, Inter-branch due-from/to, Retained earnings, FX gain/loss, Rounding. A module's **enable wizard refuses to finish until required mappings exist** (offers to auto-create from the COA template).
7.5 **Number sequences**: gap-free (row lock + transaction), per company/branch/document type/fiscal year, pattern tokens `{PREFIX}-{BRANCH}-{YYYY}-{SEQ:6}`; separate sequence for drafts vs posted if configured.
7.6 **Fiscal periods**: year/period tables; **locked periods reject any post/edit/reversal** except by permission `accounting.override-lock` (logged). Year-end closing entries wizard with rollback-safe preview.
7.7 **Multi-currency**: base currency per company; document currency + rate snapshot; line-level base amounts; realized FX on settlement; unrealized revaluation run; rate history table.
7.8 **Tax**: tax rates & groups (inclusive/exclusive, compound), per-line tax, rounding at line vs doc (setting), tax account mapping, withholding.
7.9 **Subledgers**: AR/AP control accounts reconcile to contact ledgers; a **reconciliation report** proves control account = Σ subledger (test it).
7.10 **Performance**: `account_balances` snapshot table (company, branch, account, period, debit, credit) updated on post and rebuildable via `accounting:rebuild-balances`; reports read snapshots + the open period's lines. Index `journal_lines` on (`company_id`,`account_id`,`branch_id`,`entry_date`).
7.11 **COA templates**: `generic-trading`, `generic-service`, `manufacturing`, `bd-standard` (with localized names, Bangla labels). Importable/exportable.
7.12 **Audit**: every post/reverse/lock/unlock/mapping change/module toggle is audited (who, when, IP, before/after).
7.13 **Attachments & narration** on every voucher; **maker–checker** (approval thresholds configurable per company/branch).

---

## 8. DATA & CODE CONVENTIONS
- Every business table: `id` (bigint), `uuid`, `company_id`, `branch_id` (where branch-scoped), `created_by`, `updated_by`, timestamps, soft deletes (master data only; **never** soft-delete posted accounting docs).
- Money `decimal(20,4)`; quantities `decimal(20,6)`; rates `decimal(20,10)`. Dates stored in UTC with branch timezone display; accounting dates are `date` columns (no timezone drift).
- Enums for statuses/types (backed enums, translatable labels). Form validation in Filament schemas **and** DTO/Action validation (double safety for API/import).
- Global scopes: `CompanyScope` (always), `BranchScope` (branch-scoped models). Provide `withoutBranchScope()` only inside audited services.
- Eager-load by default on list screens; use `drutogoti` to prove zero N+1 on each phase's screens.
- Indexes designed with the migration (composite indexes for list filters + report queries). Chunk/queue anything > 1,000 rows. Imports/exports/report PDFs run on queues with progress notifications.
- No business logic in Livewire/Filament classes beyond calling Actions.

---

## 9. FILAMENT UI STANDARDS
- One Filament panel `app` (path `/app`), tenancy = Company, top-bar **branch switcher**, module-aware navigation groups: *Dashboard · Sales · Purchases · Inventory · Money · Accounting · People · Projects · Growth · Intelligence · Reports · Settings*.
- Each resource: searchable/sortable/filterable tables, saved filters, bulk actions, import/export, attachments & activity timeline tabs, approval actions, PDF/print actions (via `PdfRenderer`), relation managers, status badges, keyboard-friendly line-item repeaters (inline add, barcode scan, quick-create option).
- **Dashboard**: module-aware widgets only (cash position, receivables, payables, revenue vs expense, top items, low stock, attendance today, payroll due, budget variance, pending approvals, "needs attention" inbox). Respect branch filter + date range.
- Dark/light mode, RTL support, Bangla/English switch per user, accessible forms (labels, focus order), mobile-friendly layouts (POS and attendance check-in must work on phones/tablets).
- Plugins to register in the `app` panel: `AttendanceHubPlugin` (gated by `attendance`), `AiHubPlugin` (gated by `ai-assistant`, super admin only), `AiCatalogPlugin` (gated by `inventory` + `ai-assistant`).

---

## 10. REPORTS (all branch-aware, comparable periods, drill-down to source documents, export PDF via `PdfRenderer` + XLSX + CSV, queued when large)

Financial: General Ledger, Trial Balance (with/without opening), Income Statement, Balance Sheet, Cash Flow, Day Book, Cash Book, Bank Book, Account Statement, Journal Register, Branch Comparison (side-by-side), Consolidated with eliminations, Period Comparison/YoY, Budget vs Actual.
Receivables/Payables: Aged AR/AP, Customer/Supplier Statements & Ledgers, Outstanding invoices/bills, Collection effectiveness, Cheque register.
Sales/Purchases: Sales by item/customer/salesperson/branch/category/day, Purchase analysis, Margin report, Returns, Discount analysis, POS Z-report & shift report.
Inventory: Stock on hand by branch/warehouse, Stock ledger/movement, Valuation (FIFO/avg), Aging/expiry, Reorder, Dead stock, Transfers in transit, Count variance.
People: Employee register, Attendance daily/monthly/late/absent/OT, Leave balances, Payroll register, Payslips, Bank advice, PF/tax summaries.
Assets/Tax: Fixed asset register, Depreciation schedule, Disposal; VAT/GST summary, Input/Output reconciliation, TDS/VDS, BD Mushak worksheets (pack).
Operations: Production cost, WIP, Project P&L, Timesheet utilization, CRM funnel.
Platform: AI usage & cost, Module usage, Audit report, Slug/Catalog audit.

Implement reports as **`ReportDefinition` classes** (key, title, module, parameters schema, query, columns, totals, drill-down) registered per module. The UI, PDFs, API and the AI "Ask your books" feature all consume the same definitions (this is also what keeps the AI safe: it can only choose a definition + validated parameters).

---

## 11. SECURITY, PRIVACY, COMPLIANCE
- Least privilege: permissions per module/action (`sales.invoice.create|approve|void`, …), field-level hiding for cost/margin and salary data; **branch isolation tests** (section 5).
- Validate & authorize **every** Livewire action; signed URLs for downloads; per-company storage disks/paths; antivirus hook interface for uploads.
- Public invoice links: 40+ char random tokens, expiry, revocable, no enumeration, rate-limited, show only that document.
- 2FA (TOTP) for owners/accountants (Filament 2FA or Fortify), session management, login throttling, strong password rules, optional IP allow-list for the Console.
- Encrypt sensitive columns (bank details, tax IDs, device credentials, API tokens) with Laravel `encrypted` casts; **never log secrets, biometric templates, or full card/bank numbers**.
- AI: PII redaction before prompts; per-company AI on/off; spend caps; human-in-the-loop only.
- Backups hook (DB dump + storage) with documented restore test; GDPR-style export/erase for personal data **without** breaking ledger integrity (anonymize contacts, keep journals).
- Security headers, CSP where feasible, CSRF everywhere, mass-assignment guarded, rate limits on auth/API/ADMS endpoints, ADMS endpoint validates device serial against registered devices.
- Dependency hygiene: `composer audit` in CI; lockfile committed.

---

## 12. PERFORMANCE
- Redis cache for module state, settings, permissions, report snapshots; queue everything heavy (`default`, `reports`, `ai`, `attendance`, `low`).
- `account_balances` snapshots; keyset pagination on big tables; chunked exports; DB indexes verified with `EXPLAIN` for the 10 heaviest queries (record in PROGRESS.md).
- Target (seeded demo with 100k journal lines, 20k items, 5 branches): list screens < 300 ms server time, Trial Balance < 2 s, dashboard < 1.5 s, POS add-to-cart < 100 ms.
- `drutogoti` loop as in 3.1 is part of every phase gate.

---

## 13. TESTING & QUALITY GATES
- **Pest** feature + unit tests for every Action/PostingRule/Policy; Livewire/Filament tests for each resource (list, create, edit, validation, permission, branch isolation).
- Module toggle suite (4.6) for all 24 modules; **accounting invariants** tests (balance, immutability, period lock, idempotent posting, subledger = control account, FX, rounding).
- Package smoke tests via adapters using fakes: `AIHub::fake()`, `AttendanceHub::fake()`, `UnicodePdf::fake()`, plus one real render test per PDF type and a real `UniversalSlug` test with Bangla/Arabic/CJK titles.
- Larastan ≥ level 6, Pint, `composer audit`, `php artisan modules:doctor`, `php artisan app:doctor`, `php artisan route:list` without collisions, migrations `migrate:fresh --seed` must succeed in CI.
- CI file (`.github/workflows/ci.yml`) running all of the above on PHP 8.3 + MySQL 8 + Redis.

---

## 14. SEED / DEMO DATA
- `php artisan app:demo {preset}` builds a realistic demo for each preset in section 4.7: company, 3–5 branches (HQ, 2 shops, 1 warehouse, 1 factory where relevant), users per role, COA, 200+ items, 100 customers/suppliers, 6 months of sales/purchases/payments/POS shifts/payroll/attendance (via `attendance:simulate`), assets, budgets, **Bangla and English** content, so every report and dashboard widget shows data.
- Demo logins documented in `README.md`.

---

## 15. PHASED DELIVERY PLAN (do in this order; gate each phase)

| Phase | Scope | Acceptance (all must pass) |
|---|---|---|
| **0 Foundation & spikes** | Laravel 12 app, Filament 3 panel `app`, composer config for dev-main, install all 7 packages, adapters + fakes, **Filament ⇄ admin-panel coexistence spike**, route map, CI, Pint/Larastan, Redis/queue, drutogoti on | every package boots; smoke tests green; `/app`, `/admin`, `/drutogoti`, `/ai-hub`, `/ai-catalog` reachable and protected; `docs/PACKAGE-NOTES.md` filled from real vendor code |
| **1 Core + Module system + Multi-branch** | `core`, `ModuleManager`, `BaseModule`, `HasModuleGate`, presets, Settings→Modules UI, company/branch/users/roles, branch switcher & scope, number sequences, attachments, audit, import/export engine, localization packs, universal-slug on Company/Branch | module toggle suite passes for a dummy module; branch isolation tests pass; `modules:doctor` green |
| **2 Accounting engine** | COA + templates, journals, posting service, periods, approvals, account mapping, FX, tax foundation, GL/TB/P&L/BS/CF/Day Book, snapshots | all invariants in 7.x tested; reports tie out; PDFs render Bangla/English |
| **3 Money & parties** | `contacts`, `banking`, `expenses`, `tax` | receipts/payments allocate; reconciliation works; AR/AP control tie-out |
| **4 Trade** | `sales`, `purchases` (+ public invoice link, credit/debit notes) | full quote→cash and RFQ→payment cycles; ledger postings correct; PDFs; module-off graceful |
| **5 Stock** | `inventory` (+ AI Catalog + universal slugs + transfers + valuation), then `catalog-web` | FIFO & average costing verified by tests; AI Catalog draft→approve flow; slug 301 on rename |
| **6 POS** | `pos` | shift open→sales→return→close posts correct cash over/short; thermal receipt PDF |
| **7 People** | `hr`, `attendance` (device registry, ADMS, summaries), `payroll` | simulated devices → summaries → payroll run → payslip PDF (Bangla) → journal |
| **8 Growth & assets** | `fixed-assets`, `budgeting`, `projects`, `crm`, `manufacturing`, `collections`, `documents` | each with toggle suite; cross-module graceful degradation verified |
| **9 Intelligence & integration** | `ai-assistant`, `messaging`, `api` (+ webhooks, OpenAPI) | AI features draft-only & fake-tested; API scopes respect branch isolation |
| **10 Hardening & release** | Console dashboard (admin-panel), perf pass on 100k-line dataset, security review, backups, CI green, all docs, demo seeders per preset | Definition of Done below |

---

## 16. DEFINITION OF DONE (final checklist — verify literally)
- [ ] `composer install && php artisan migrate:fresh --seed && php artisan app:demo retail` works on a clean machine (steps in `docs/INSTALL.md`, incl. Supervisor workers, cron, nginx ADMS snippet, `unicode-pdf:font:install`, `storage:link`).
- [ ] All **24 modules** exist, toggle ON/OFF per company and per branch, with dependency guards, enable wizards, and zero errors elsewhere when OFF.
- [ ] Multi-branch: switcher, scoped data, per-branch numbering, inter-branch transfers/clearing, consolidated + comparison reports, isolation tests green.
- [ ] Ledger invariants (7.1–7.13) proven by tests; posted records immutable; period lock works.
- [ ] All 7 packages are used exactly as specified in section 3 and wrapped by adapters; each has fake + smoke tests.
- [ ] Every document and report prints correctly in **Bangla, Arabic and English** (no tofu/`???`), with native numerals option.
- [ ] No unresolved N+1/slow queries on phase screens per `/drutogoti`; performance targets met.
- [ ] Security checklist (section 11) implemented; `composer audit` clean.
- [ ] `php artisan modules:doctor` and `app:doctor` green; Pint, Larastan, Pest all green in CI.
- [ ] Docs complete: README, INSTALL, MODULES (with integration/degradation matrix), MODULE-AUTHORING, DECISIONS, PACKAGE-NOTES, PROGRESS.
- [ ] Demo data for every preset with Bangla + English content.

**Begin now with Phase 0. Create `docs/PROGRESS.md`, then proceed phase by phase without stopping until Section 16 is fully satisfied.**