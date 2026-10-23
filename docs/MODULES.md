# Module runtime and delivery status

The registry in config/modules.php contains all 24 manifests. The core tenant/module runtime is functional. Accounting currently has a mandatory manifest only; the 22 optional modules currently have manifests and dependency metadata, not complete business features.

Company owners manage state at `/app/{company:slug}/modules`. Core and accounting cannot be disabled. Optional state is stored per company; a branch may disable an enabled company module, but cannot enable a company-disabled module. Required dependencies block enable/disable operations. Toggles preserve settings and data and produce module_events plus a chained audit entry.

The ModuleManager is the runtime source of truth. Redis/default-cache company snapshots and request-scoped membership/module caches avoid one query per card. The `module:key` middleware returns 404 for web requests and 403 for JSON requests. HasModuleGate controls page/navigation access. `@module('key')` handles Blade sections. ModuleRuntime runs queued/listener work under an explicit company/branch context and skips disabled modules.

```bash
php artisan modules:list
php artisan modules:doctor
php artisan modules:enable 1 contacts --actor=owner@example.com --reason="Enable contact management"
php artisan modules:disable 1 contacts --actor=owner@example.com --reason="Suspend contact module"
```

Use `--branch=ID` for a sparse branch override. The command actor must be an owner of the requested company. There is no unrestricted force switch.

Onboarding presets are configured in config/presets.php and applied transactionally in dependency order. Enabling a manifest does not create fictional business records or claim its features are implemented.

| Integration behavior | Delivery status |
| --- | --- |
| Sales without inventory: service/free-text lines | Pending sales module |
| Purchases without inventory: expense lines | Pending purchases module |
| Payroll without attendance: manual days/overtime | Pending payroll module |
| Sales without POS: omit register/shift fields | Pending sales/POS modules |
| Existing data/settings survive module disable/re-enable | Implemented and tested for every optional manifest |
| Branch override leaves company-wide module enabled | Implemented and tested |
| Disabled module work is skipped | Implemented and tested via ModuleRuntime |
| Module-specific open-item/mapping guards | Pending their owning domain modules |

The manifest/toggle tests are infrastructure coverage. They are not substitutes for feature, resource, report, posting-rule and module-off integration tests when business modules are added.
