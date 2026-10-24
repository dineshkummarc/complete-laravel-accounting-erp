<?php

declare(strict_types=1);

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Core\Contracts\UploadScanner;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Models\Branch;
use App\Modules\Core\Services\AuditTrail;
use App\Modules\Core\Services\BusinessContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class AddAttachment
{
    public function handle(User $actor, Branch|JournalEntry $subject, UploadedFile $file): Attachment
    {
        Gate::forUser($actor)->authorize($subject instanceof Branch ? 'update' : 'attach', $subject);
        $context = app(BusinessContext::class);
        abort_unless($context->branchId === ($subject instanceof Branch ? $subject->id : $subject->branch_id), 403);
        app(UploadScanner::class)->scan($file);
        $path = $file->store('companies/'.$context->companyId.'/'.$context->branchId.'/attachments', 'local');
        if (! is_string($path)) {
            throw new \RuntimeException('Unable to store upload.');
        }
        try {
            return DB::transaction(function () use ($path, $subject, $file, $actor, $context): Attachment {
                $attachment = new Attachment(['subject_type' => $subject::class, 'subject_id' => $subject->id, 'disk' => 'local', 'path' => $path,
                    'original_name' => basename($file->getClientOriginalName()), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
                    'sha256' => hash_file('sha256', $file->getRealPath())]);
                $attachment->created_by = $actor->id;
                $attachment->save();
                app(AuditTrail::class)->record($context->companyId, $context->branchId, $actor->id, 'attachment.created', Attachment::class, (string) $attachment->id);

                return $attachment;
            });
        } catch (Throwable $e) {
            Storage::disk('local')->delete($path);
            throw $e;
        }
    }
}
