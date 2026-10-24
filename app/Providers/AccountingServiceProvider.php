<?php

declare(strict_types=1);

namespace App\Providers;

use App\Modules\Accounting\Listeners\InitializeCompanyAccounting;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Policies\AccountPolicy;
use App\Modules\Accounting\Policies\JournalEntryPolicy;
use App\Modules\Accounting\Services\AccountChoices;
use App\Modules\Accounting\Services\LedgerWriteGuard;
use App\Modules\Core\Events\CompanyCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AccountingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(LedgerWriteGuard::class);
        $this->app->scoped(AccountChoices::class);
    }

    public function boot(): void
    {
        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);
        Gate::policy(Account::class, AccountPolicy::class);
        Event::listen(CompanyCreated::class, InitializeCompanyAccounting::class);
    }
}
