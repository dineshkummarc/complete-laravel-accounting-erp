<?php

namespace ImranDevBd\AiCatalog\Console;

use ImranDevBd\AiCatalog\Jobs\RetryFailedGenerationsJob;
use Illuminate\Console\Command;

class RetryFailedCommand extends Command
{
    protected $signature = 'ai-catalog:retry-failed {--sync : Run immediately instead of queuing}';

    protected $description = 'Re-queue failed AI catalog generations';

    public function handle(): int
    {
        if ($this->option('sync')) {
            (new RetryFailedGenerationsJob)->handle();
            $this->info('Failed generations retried synchronously.');

            return self::SUCCESS;
        }

        RetryFailedGenerationsJob::dispatch()->onQueue((string) config('ai-catalog.queue', 'default'));
        $this->info('Failed generations dispatched to the queue.');

        return self::SUCCESS;
    }
}
