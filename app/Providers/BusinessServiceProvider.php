<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Modules\Core\Contracts\UploadScanner;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Models\AuditEvent;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Models\Company;
use App\Modules\Core\Models\ImportBatch;
use App\Modules\Core\Modules\ModuleManager;
use App\Modules\Core\Policies\AttachmentPolicy;
use App\Modules\Core\Policies\AuditEventPolicy;
use App\Modules\Core\Policies\BranchPolicy;
use App\Modules\Core\Policies\CompanyPolicy;
use App\Modules\Core\Policies\ImportBatchPolicy;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\CompanyAccess;
use App\Modules\Core\Services\SafeUploadScanner;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class BusinessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(BusinessContext::class);
        $this->app->scoped(CompanyAccess::class);
        $this->app->bind(UploadScanner::class, SafeUploadScanner::class);
        $this->app->scoped(ModuleManager::class);
    }

    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(ImportBatch::class, ImportBatchPolicy::class);
        Gate::policy(AuditEvent::class, AuditEventPolicy::class);
        Gate::policy(Attachment::class, AttachmentPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Blade::if('module', fn (string $key): bool => app(ModuleManager::class)->isEnabled($key));
    }
}
