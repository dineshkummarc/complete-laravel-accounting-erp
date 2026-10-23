# Module authoring boundary

The module runtime is not implemented. Do not build feature resources against an assumed registry API. Follow REQUIREMENTS.md sections 4.2–4.6 for the manifest, dependency semantics, runtime gates and mandatory toggle tests when Phase 1 begins.

All package calls must remain under app/Integrations. Contracts there currently cover AI suggestions, catalog drafts, PDF rendering, attendance provider capabilities, Console authorization and profiler summaries. Test doubles live under app/Integrations/Fakes; production bindings use the real implementations in IntegrationServiceProvider.

CompanyScope, BranchScope, BaseModule, HasModuleGate, PostingRule, ReportDefinition, number sequences, and module-aware policies remain required implementation work. No scaffold is presented as a functional business module.
