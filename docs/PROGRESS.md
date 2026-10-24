# Delivery progress

## Status

The application remains in development. The core tenant platform and an initial accounting engine are implemented. All 24 module manifests are registered; optional manifests do not constitute completed business workflows. No production release or completion of the master requirements is claimed.

## Implemented foundation and core

- Laravel 12, Filament 3, independent Console, shared authentication, tenant-scoped roles and the seven locked required packages.
- Company onboarding with headquarters, localization packs, presets, default chart and fiscal-year initialization; no fictional business transactions seeded.
- Memberships, explicit staff branch access, branch switching, settings, English/Bangla labels and immutable company slugs.
- Module dependencies, company/branch enable state, cache invalidation, nav/route/job guards, presets and audited toggles.
- Transactional branch numbering, private scanned attachments and signed downloads, queued branch CSV imports with row errors and duplicate-job protection, formula-safe CSV exports.
- Tenant-scoped package auditing and HMAC-chained audit events, integrity viewer, read/write policies and fail-closed company/branch scopes.
- Privacy-filtered performance capture, package-backed storage/analyzer, fingerprint-based suggestion handling and bounded pruning. Package deviations are recorded in PACKAGE-NOTES.md; vendor files were not modified.

## Implemented accounting checkpoint

- Chart of accounts and mappings with company defaults and branch overrides. Four template keys are available; generic service omits stock accounts, while other templates currently share the broad default chart.
- Monthly fiscal periods, overlap checks, create-year UI, reasoned lock/unlock and explicit authorized lock overrides.
- Immutable posted journals and lines, transactional numbering, source/version fingerprints, idempotency, separate maker/checker approval and configurable company threshold.
- Exact decimal arithmetic using Brick Math/Money, document/base amounts and exchange-rate snapshots. Bounded conversion rounding creates an explicit base-only rounding line. Reversals preserve original facts and post opposite linked entries in an open period.
- Transactional account-balance snapshots, exact rebuild action and `accounting:rebuild-balances {company} {branch} --actor={user}` command.
- PostingRule contract and guarded PostDocumentToLedger action; domain rules are added with their owning business workflows.
- Filament chart, journals, accounting settings and report screens. Browser verified draft creation, posting and a balanced trial balance in a disposable local verification company.
- Trial balance, income statement, balance sheet, ledger/day book, journal register, account statements, cash/bank books and branch comparison. Shared definitions, authorized branch scope, opening balances, snapshot/full-period plus partial-period line reading, CSV formula protection and limited previews.
- Cash-flow currently provides opening/movement/closing cash reconciliation only; the required indirect activity classification remains pending. Consolidated currently combines authorized branches without automatic interbranch elimination.

## Tax and voucher extension

- Configurable tax rates, ordered groups and account mappings, with no assumed statutory percentages. Inclusive, exclusive, compound and withholding calculations use exact rational arithmetic and return rate and component facts for source documents.
- Line/document rounding at a configured 0–4 decimal scale. Inclusive rounding residuals are allocated to the last included component so the input gross is preserved. Withholding uses the net base, reduces payable and cannot be inclusive or compound.
- Rate/group creation and a non-posting calculator are available in English/Bangla. Browser creation and calculation were checked with disposable example settings; proof is in screenshots/accounting-tax-calculation.png.
- Private scanned voucher attachments can be added to drafts or posted journals. Attachment downloads require ledger access in addition to branch membership; adding evidence does not mutate journal amounts or status.
- Unsupported currency and conversion overflow return validation errors before any journal write.
- Bank/cash book filters respect branch mapping overrides rather than including both default and overridden accounts.

## Verification

- SQLite: 105 tests / 597 assertions passed, including ledger, tax, attachment authorization and bank-book mapping regressions. Tests cover posting precision, imbalance rollback, source identity, immutability, approval, locks, reversals, rounding, rebuilds, report reconciliation and company/branch access.
- Isolated MariaDB: 105 tests / 597 assertions passed with the same ledger, tax, attachment and mapping regressions.
- Pint and Larastan level 6 passed for this checkpoint. Route cache compilation passed.
- Core screen proof: screenshots/core-branches.png. Accounting browser proof: screenshots/accounting-trial-balance.png. Screenshots show disposable verification data, not seeded production records.
- Local PHP is 8.5; Composer targets PHP 8.3. PHP 8.3/MySQL 8 CI is configured but its execution has not been observed. Docker deployment remains unverified.
- Native PDF generation and font installation were tested earlier; multilingual document shaping/golden checks remain open.

## Remaining accounting scope

Draft editing, recurring journals, opening-balance wizard, cost-center management, settlement and revaluation FX, year-end close, reconciliation/subledger controls, branch-specific approvals, mapping enable-wizard checks, complete template variants, fiscal-date mutation guards, queued large exports, XLSX/PDF reports, indirect cash flow and interbranch elimination remain unfinished. Report CSV currently materializes the result in memory; large export jobs are still required. The accounting phase is not signed off.

## Remaining business and release scope

Contacts, banking, expenses, tax returns, sales, purchases, inventory, POS, HR, attendance, payroll, manufacturing, assets, projects, growth, messaging, AI business integrations, public API and final security/performance/deployment acceptance remain incomplete. See REQUIREMENTS.md for the complete workflow and acceptance list. Account IDs on contacts/cost centers and business posting rules will be validated against their owning domain records as those modules are implemented.
