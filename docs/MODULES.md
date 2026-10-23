# Module delivery status

The required module architecture is specified in REQUIREMENTS.md sections 4–6. No runtime business module registry, company/branch module toggles, or business module is implemented yet. Installed Composer packages are integrations, not proof that business modules are complete.

All 24 requested modules remain pending: core, accounting, contacts, sales, purchases, inventory, pos, banking, expenses, fixed-assets, hr, attendance, payroll, crm, projects, manufacturing, budgeting, tax, collections, messaging, documents, catalog-web, ai-assistant, api.

The required degradation behavior remains acceptance criteria: sales without inventory uses service lines; purchases without inventory uses expense lines; payroll without attendance uses manual days/overtime; sales without POS omits register/shift fields. These behaviors have not been implemented or tested. Device ingestion, catalog API, and automatic AI navigation are closed until their module gates and tenant scopes exist.
