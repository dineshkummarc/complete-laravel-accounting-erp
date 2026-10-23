<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\Core\Modules\ModuleManager;
use Illuminate\Console\Command;

final class ToggleModule extends Command
{
    protected $signature = 'modules:toggle {company} {key} {--disable} {--branch=} {--actor= : Company owner email} {--reason=}';

    protected $description = 'Change module state with company-owner authorization and an audited reason';

    public function handle(ModuleManager $manager): int
    {
        $actor = User::query()->where('email', $this->option('actor'))->firstOrFail();
        $manager->toggle($actor, (int) $this->argument('company'), $this->argument('key'), ! $this->option('disable'), (string) $this->option('reason'), $this->option('branch') === null ? null : (int) $this->option('branch'));
        $this->info('Module state updated.');

        return self::SUCCESS;
    }
}
