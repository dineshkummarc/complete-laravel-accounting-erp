<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Contracts\UploadScanner;
use App\Modules\Core\Jobs\ProcessImport;
use App\Modules\Core\Models\ImportBatch;
use App\Modules\Core\Services\BusinessContext;
use App\Modules\Core\Services\ImportRegistry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class QueueImport
{
    public function handle(User $actor, string $definition, UploadedFile $file): ImportBatch
    {
        $context = app(BusinessContext::class);
        abort_unless(DB::table('company_user')->where('company_id', $context->companyId)->where('user_id', $actor->id)->where('is_owner', true)->exists(), 403);
        app(ImportRegistry::class)->get($definition);
        app(UploadScanner::class)->scan($file);
        $path = $file->store('companies/'.$context->companyId.'/imports', 'local');
        if (! is_string($path)) {
            throw new \RuntimeException('Unable to store import.');
        }
        try {
            $batch = DB::transaction(function () use ($actor, $definition, $path): ImportBatch {
                $batch = new ImportBatch(['definition' => $definition, 'path' => $path, 'status' => 'queued']);
                $batch->created_by = $actor->id;
                $batch->save();
                ProcessImport::dispatch($batch->company_id, $batch->branch_id, $batch->id)->afterCommit();

                return $batch;
            });

            return $batch;
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }
}
