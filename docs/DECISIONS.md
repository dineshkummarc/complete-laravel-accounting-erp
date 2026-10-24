# Architecture decisions

| Date | Decision | Reason |
| --- | --- | --- |
| 2026-10-24 | Preserve supplied requirements verbatim in REQUIREMENTS.md. | Keep acceptance criteria reviewable alongside code. |
| 2026-10-24 | Resolve Composer against PHP 8.3.0, retaining Laravel 12 and Filament 3. | Host PHP 8.5 otherwise selects Symfony 8 dependencies that cannot run on the required target. |
| 2026-10-24 | Use two independent panel layouts, `/app` and `/admin`, with one users table and web guard. | Matches the specified business/Console split; avoids mixing frontend runtimes. |
| 2026-10-24 | Reserve Spatie team ID 0 for platform bootstrap only. | Company and branch membership do not exist until Phase 1; avoid creating a second RBAC source. |
| 2026-10-24 | Authenticate and authorize package management screens with application middleware. | Package local/testing authorization defaults are permissive. |
| 2026-10-24 | Disable catalog package API and all attendance ingress until tenant/device authorization exists. | Public defaults cannot satisfy company/branch isolation. |
| 2026-10-24 | Supply a host-owned profiler summary and override Console dashboard view. | Installed profiler has no dashboard; installed Console uses static sample metrics. |
| 2026-10-24 | Keep profiler capture disabled by default. | Its raw query bindings may contain sensitive information; advertised sampling/exclusions are not enforced by installed source. |
| 2026-10-24 | Seed permissions only; install the initial owner interactively. | Avoid production mock records and default credentials. |
| 2026-10-24 | Store and explicitly register PDF fonts under storage. | Vendor config contains developer-specific Windows paths; vendor files remain untouched. |
| 2026-10-24 | Do not certify Phase 0 or begin Phase 1 before its complete acceptance gate. | User expressly requires phases to pass in order. |
| 2026-10-24 | Replace unsafe profiler collection with host RequestCapture while retaining the package storage job and analyzer. | Enforce sampling/exclusions, remove SQL literals/bindings and hash cache keys without vendor edits. |
| 2026-10-24 | Use a temporary extracted matching GD extension for host validation. | Verifies required capability without mutating system PHP configuration. |


## Accounting checkpoint decisions

- Production uses DECIMAL(20,4) and DECIMAL(20,10). SQLite uses TEXT for these fields because numeric affinity silently converts large exact decimals to floating point. All arithmetic uses Brick Math; application amounts remain strings.
- Date-only model casts persist `Y-m-d` across drivers. Timestamps remain full UTC datetimes. A SQLite normalization migration fixes early date/time storage and same-day report exclusion.
- Account-mapping branch ID 0 denotes a company default; an actual branch ID is an override.
- Currency conversion residuals are permitted only for document-balanced entries and bounded by the maximum four-place line rounding error. The base-only adjustment is explicit and marked; document imbalance is never automatically repaired.
- A reversal awaiting independent approval does not mark its original reversed until the reversing entry posts. Original rows remain reportable facts; reports include both posted and reversed originals plus posted reversals.
- Report preview limits do not change totals. Current CSV exports compute the full result in memory; queued large exports remain a release requirement.
- Cash-flow reconciliation is provisional and is labelled in the result metadata. It must not be treated as the requested complete indirect cash-flow statement.
