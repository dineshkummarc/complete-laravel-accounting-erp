# Authoring modules

Create `app/Modules/Name/NameModule.php` extending `App\Modules\Core\Modules\BaseModule` and register its key/class in config/modules.php. Add English and Bangla names under lang/{locale}/modules.php. The registry is code-owned; database rows store runtime state only.

The base manifest exposes dependency/conflict metadata, permissions, settings schemas, account-mapping requirements, enable checks, lifecycle hooks, scheduled tasks, widgets, report definitions and search providers. Override only the capabilities the module supplies. Core/accounting cannot be disabled. Optional integrations must degrade gracefully when absent.

All business models extend BusinessModel, which fails closed without company context. Set branchScoped for branch records; missing branch context then returns no rows. Company/branch identity and creator/UUID cannot be reassigned on normal model updates. BusinessContext selects an authenticated member's company and authorized branch; queued work uses its scoped run method and restores the previous context in finally.

Use Actions for authorization, validation and transactions. Filament pages call Actions. Use HasModuleGate for module pages/resources and `module:key` for routes. Run jobs/listener work through ModuleRuntime or equivalent explicit gating. Use the installed package contracts under app/Integrations rather than calling vendor packages from domain code.

Use AuditTrail for business changes requiring tamper-evident history. Its HMAC chain uses APP_KEY; preserve that key in backup/restore procedures. Company, Branch and Role also use the installed master-data auditing package with a company resolver. Never audit secret fields.

Imports implement ImportDefinition and register in ImportRegistry; the current concrete definition imports branches. ProcessImport streams CSV, validates each row through its Action, records progress/errors and ignores duplicate delivery after claiming a batch. UploadScanner is a replaceable hook; the default performs size/type/content checks and optionally invokes a configured antivirus executable. Configure a real scanner before accepting untrusted production uploads.

Private files stay under storage/app/private/companies. Downloads require signed URLs and current company/branch authorization. Exports must escape formula-leading CSV cells. Money belongs in decimal columns and Brick value objects; never use floating-point arithmetic.

Run Pest, Pint, Larastan level 6, modules:doctor and fresh migrations against SQLite and MySQL/MariaDB. Add module-off, dependency, branch override, data restoration, permission and workflow tests. A manifest is not evidence that a module is feature-complete.
