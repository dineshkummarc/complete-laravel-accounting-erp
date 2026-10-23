<?php

declare(strict_types=1);

namespace App\Modules\Core\Jobs;

use App\Models\User;
use App\Modules\Core\Models\ImportBatch;
use App\Modules\Core\Modules\ModuleManager;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\ImportRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class ProcessImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(public int $companyId, public int $branchId, public int $batchId) {}

    public function handle(BusinessContext $context, ImportRegistry $registry): void
    {
        $context->run($this->companyId, $this->branchId, function () use ($context, $registry): void {
            $batch = ImportBatch::query()->findOrFail($this->batchId);
            $definition = $registry->get($batch->definition);
            if (! app(ModuleManager::class)->isEnabled($definition->module())) {
                return;
            }
            $actor = User::query()->findOrFail($batch->created_by);
            $context->select($actor, $this->companyId, $this->branchId);
            $claimed = ImportBatch::query()->whereKey($batch->id)->where('status', 'queued')->update(['status' => 'processing']);
            if (! $claimed) {
                return;
            }
            $stream = Storage::disk('local')->readStream($batch->path);
            if (! is_resource($stream)) {
                $batch->update(['status' => 'failed']);

                return;
            }
            $processed = 0;
            $failed = 0;
            $errors = [];
            try {
                $header = fgetcsv($stream, null, ',', '"', '');
                if ($header !== $definition->columns()) {
                    throw new \RuntimeException('CSV header must be: '.implode(',', $definition->columns()));
                }
                while (($row = fgetcsv($stream, null, ',', '"', '')) !== false) {
                    if ($row === [null]) {
                        continue;
                    }
                    $processed++;
                    try {
                        if (count($row) !== count($header) || in_array(null, $row, true)) {
                            throw new \RuntimeException('Invalid CSV column count.');
                        }
                        DB::transaction(fn () => $definition->import($actor, array_combine($header, $row)));
                    } catch (ValidationException $e) {
                        $failed++;
                        if (count($errors) < 100) {
                            $errors[] = ['row' => $processed + 1, 'message' => $e->getMessage()];
                        }
                    }
                    $batch->update(['processed' => $processed, 'failed' => $failed]);
                }
                $batch->update(['status' => $failed > 0 ? 'completed_with_errors' : 'completed', 'errors' => json_encode($errors, JSON_THROW_ON_ERROR)]);
            } catch (Throwable) {
                $batch->update(['status' => 'failed', 'processed' => $processed, 'failed' => $failed, 'errors' => json_encode([['message' => 'Import failed. Verify CSV format and company permissions.']], JSON_THROW_ON_ERROR)]);
                throw new \RuntimeException('Import failed.');
            } finally {
                fclose($stream);
            }
        });
    }
}
