# Installed package inspection

Inspected real composer manifests, README documentation, configuration, service providers, and migration sources under vendor/imrandevbd on 2026-10-24. Lockfile pins the following revisions. No vendor source was edited.

| Package | Installed revision | Verified surface and integration |
| --- | --- | --- |
| drutogoti | 6c5b4c9 | Drutogoti namespace; QueryWatcher and CaptureContext; published six migration stubs. Adapter reads actual telemetry tables. |
| laravel-admin-panel | 6730519 | ImranDevBD namespace; Panel registry, themes, Blade components, dashboard/theme/assets routes. Shared web guard; host middleware enforces permissions. |
| laravel-ai-hub | v1.6.0 | AIHub structured(schema)->forJob(job)->prompt(text)->generate(); response json(), success; fake(); auth(callback). |
| laravel-ai-catalog | 07b5783 | AiCatalog fromText()->generate(); CatalogGeneration primary key; requires hub ^1.6. Adapter exposes draft creation only. |
| laravel-attendance-hub | 7bd5a7b | AttendanceHub provider()->supportsPush(); fake(); migration autoload; catalog command reports 166 supported models across 10 brands. |
| laravel-unicode-pdf | bc0288d | ImranDev namespace; preset()->loadHtml()->output(); fake(); bundled native renderer; storage font installer. |
| laravel-universal-slug | 9fdcf16 | ImranDev namespace; UniversalSlug::generate(); migration stub for slug histories. Real Bengali generation exercised. |

## Source versus advertised behavior

1. **Drutogoti has no dashboard route/controller/view**, despite dashboard configuration and README descriptions. Service provider registers only QueryWatcher. Application-owned `/drutogoti` shows actual request, slow-query, and unresolved-suggestion counts. Regression coverage verifies authorization and route reachability.
2. **Drutogoti CaptureContext does not enforce sample_rate or root exclude_paths.** QueryWatcher stores raw bindings and skips only telemetry-table SQL plus configured caller exclusions. Capture stays disabled pending a tested adapter-layer capture/privacy workaround. Cookie/cache watcher classes and automatic pruning are not present in installed source.
3. **Console source is much smaller than the specification's advertised feature set.** Service provider registers install/publish commands, assets, themes, navigation and three route surfaces. No resource, widget generator, health doctor, RBAC models, audit migration, log viewer or queue inspector APIs exist in this revision. InstallCommand does not create a superadmin. Host dashboard override removes all hard-coded sample metrics and shows real user/failed-job counts. Foundation tests assert that the sample revenue is absent. Full Console functionality still needs host implementation.
4. **AI Catalog Studio bypasses authorization in local/testing environments.** Host middleware always requires session auth and platform.console before package middleware. Tests prove ordinary users receive 403 even in testing. Its package API defaults public; disabled here.
5. **AI Hub adds navigation automatically.** Automatic navigation is disabled until company module gating exists. Host auth middleware and callback protect its Studio in every environment.
6. **Attendance provider auto-loads all migrations and one combined ADMS/virtual/bridge route file.** Disable the entire ingress group until secure tenant resolution is implemented. Avoid duplicate published migrations because the package already loads originals. Models mapped to webhook/ADMS drivers are not evidence of a native driver for every brand.
7. **Unicode PDF config contains Windows-specific custom font paths and an AI-Borno registration.** Host config replaces those with explicit storage-managed Noto font registrations. `unicode-pdf:font:install` can return success after download failure; app:doctor checks actual files. `checkGlyphs` is a script/font suggestion diagnostic, not exhaustive per-glyph visual proof.
8. **Package migrations are mixed auto-load/publish.** AI Hub, Catalog and Attendance auto-load migrations; Drutogoti and slug history require publication. All currently migrate cleanly on SQLite and an isolated MariaDB 11.8 instance. Exact MySQL 8 execution remains assigned to CI and is not observed yet.

## Boundaries not yet implemented

Adapters are the foundation surfaces, not complete module integrations. Company gating, tenancy, AI budgets, full PII redaction of structured domain inputs, OCR/catalog review workflows, attendance ingestion, slugged domain models, PDF golden-file verification, and per-module fakes/tests remain future phase work. The AI adapter currently validates returned suggestions and redacts email/numeric identifiers; it cannot post, approve, or pay through its contract.
