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
