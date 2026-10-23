<?php

declare(strict_types=1);
use App\Modules\Accounting\AccountingModule;
use App\Modules\AiAssistant\AiAssistantModule;
use App\Modules\Api\ApiModule;
use App\Modules\Attendance\AttendanceModule;
use App\Modules\Banking\BankingModule;
use App\Modules\Budgeting\BudgetingModule;
use App\Modules\CatalogWeb\CatalogWebModule;
use App\Modules\Collections\CollectionsModule;
use App\Modules\Contacts\ContactsModule;
use App\Modules\Core\CoreModule;
use App\Modules\Crm\CrmModule;
use App\Modules\Documents\DocumentsModule;
use App\Modules\Expenses\ExpensesModule;
use App\Modules\FixedAssets\FixedAssetsModule;
use App\Modules\Hr\HrModule;
use App\Modules\Inventory\InventoryModule;
use App\Modules\Manufacturing\ManufacturingModule;
use App\Modules\Messaging\MessagingModule;
use App\Modules\Payroll\PayrollModule;
use App\Modules\Pos\PosModule;
use App\Modules\Projects\ProjectsModule;
use App\Modules\Purchases\PurchasesModule;
use App\Modules\Sales\SalesModule;
use App\Modules\Tax\TaxModule;

return [
    'core' => CoreModule::class,
    'accounting' => AccountingModule::class,
    'contacts' => ContactsModule::class,
    'sales' => SalesModule::class,
    'purchases' => PurchasesModule::class,
    'inventory' => InventoryModule::class,
    'pos' => PosModule::class,
    'banking' => BankingModule::class,
    'expenses' => ExpensesModule::class,
    'fixed-assets' => FixedAssetsModule::class,
    'hr' => HrModule::class,
    'attendance' => AttendanceModule::class,
    'payroll' => PayrollModule::class,
    'crm' => CrmModule::class,
    'projects' => ProjectsModule::class,
    'manufacturing' => ManufacturingModule::class,
    'budgeting' => BudgetingModule::class,
    'tax' => TaxModule::class,
    'collections' => CollectionsModule::class,
    'messaging' => MessagingModule::class,
    'documents' => DocumentsModule::class,
    'catalog-web' => CatalogWebModule::class,
    'ai-assistant' => AiAssistantModule::class,
    'api' => ApiModule::class,
];
