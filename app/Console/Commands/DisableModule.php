<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Core\Modules\ModuleManager;
use Illuminate\Console\Command;

final class DisableModule extends Command
{
    protected $signature = 'modules:disable {company} {key} {--branch=} {--actor=} {--reason=}';

    protected $description = 'Disable a module with owner authorization, dependencies and audit';

    public function handle(ModuleManager $manager): int
    {
        $actor = User::query()->where('email', $this->option('actor'))->firstOrFail();
        $manager->toggle($actor, (int) $this->argument('company'), $this->argument('key'), false, (string) $this->option('reason'), $this->option('branch') === null ? null : (int) $this->option('branch'));
        $this->info('Module state updated.');

        return self::SUCCESS;
    }
}
