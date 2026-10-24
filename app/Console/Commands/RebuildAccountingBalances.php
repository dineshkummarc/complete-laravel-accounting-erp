<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Accounting\Actions\RebuildBalances;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Console\Command;

final class RebuildAccountingBalances extends Command
{
    protected $signature = 'accounting:rebuild-balances {company} {branch} {--actor= : Authorized user ID}';

    protected $description = 'Rebuild one authorized branch balance snapshot from immutable journals';

    public function handle(): int
    {
        $actor = User::query()->findOrFail($this->option('actor'));
        $context = app(BusinessContext::class);
        $context->select($actor, (int) $this->argument('company'), (int) $this->argument('branch'));
        $count = app(RebuildBalances::class)->handle($actor);
        $this->info('Rebuilt '.$count.' balance rows.');

        return self::SUCCESS;
    }
}
