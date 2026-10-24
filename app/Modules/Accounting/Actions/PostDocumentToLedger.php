<?php

declare(strict_types=1);

namespace App\Modules\Accounting\Actions;

use App\Models\User;
use App\Modules\Accounting\Enums\JournalStatus;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Posting\PostingRule;
use App\Modules\Accounting\Services\ApprovalRules;
use App\Modules\Core\Modules\ModuleManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use LogicException;

final class PostDocumentToLedger
{
    public function handle(User $actor, string $sourceType, string $sourceId, int $version = 1): JournalEntry
    {
        $class = config('posting-rules.'.$sourceType);
        if (! is_string($class) || ! is_a($class, PostingRule::class, true)) {
            throw new LogicException('No posting rule registered for this source type.');
        }
        $rule = app($class);
        if (! app(ModuleManager::class)->isEnabled($rule->module())) {
            throw ValidationException::withMessages(['module' => 'The source module is disabled.']);
        }

        return DB::transaction(function () use ($actor, $rule, $sourceType, $sourceId, $version): JournalEntry {
            $data = $rule->build($actor, $sourceId, $version);
            if ($data->sourceType !== $sourceType || $data->sourceId !== $sourceId || $data->version !== $version) {
                throw new LogicException('Posting rule returned an inconsistent source identity.');
            }
            $entry = app(CreateJournal::class)->handle($actor, $data);
            if (app(ApprovalRules::class)->required($entry) && $entry->approved_by === null && ! in_array($entry->status, [JournalStatus::Posted, JournalStatus::Reversed], true)) {
                return $entry->status === JournalStatus::Draft ? app(SubmitJournal::class)->handle($actor, $entry->id) : $entry;
            }

            return app(PostJournal::class)->handle($actor, $entry->id);
        }, 3);
    }
}
