<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Core\Modules\ModuleManager;
use Illuminate\Console\Command;

final class EnableModule extends Command
{
    protected $signature = 'modules:enable {company} {key} {--branch=} {--actor=} {--reason=}';

    protected $description = 'Enable a module with owner authorization, dependencies and audit';

    public function handle(ModuleManager $manager): int
    {
        $actor = User::query()->where('email', $this->option('actor'))->firstOrFail();
        $manager->toggle($actor, (int) $this->argument('company'), $this->argument('key'), true, (string) $this->option('reason'), $this->option('branch') === null ? null : (int) $this->option('branch'));
        $this->info('Module state updated.');

        return self::SUCCESS;
    }
}
