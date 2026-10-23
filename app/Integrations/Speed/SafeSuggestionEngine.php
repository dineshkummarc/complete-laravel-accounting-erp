<?php

declare(strict_types=1);

namespace App\Integrations\Speed;

use Drutogoti\Models\Suggestion;
use Drutogoti\Services\SuggestionEngine;
use Illuminate\Support\Facades\DB;

final class SafeSuggestionEngine extends SuggestionEngine
{
    /** @param array<string, mixed> $context */
    protected function logSuggestion(string $category, string $severity, string $title, string $description, array $context): void
    {
        $fingerprint = hash('sha256', $category.':'.$title.':'.($context['sql'] ?? ''));
        DB::transaction(function () use ($fingerprint, $category, $severity, $title, $description, $context): void {
            DB::table('performance_suggestion_keys')->insertOrIgnore(['fingerprint' => $fingerprint]);
            $key = DB::table('performance_suggestion_keys')->where('fingerprint', $fingerprint)->lockForUpdate()->firstOrFail();
            $values = ['category' => $category, 'severity' => $severity, 'title' => $title, 'description' => $description,
                'related_request_id' => $context['request_id'] ?? null, 'last_seen_at' => now(), 'is_resolved' => false];
            if ($key->suggestion_id === null) {
                $suggestion = Suggestion::query()->create($values + ['first_seen_at' => now(), 'occurrences_count' => 1]);
                DB::table('performance_suggestion_keys')->where('id', $key->id)->update(['suggestion_id' => $suggestion->id]);
            } else {
                $suggestion = Suggestion::query()->whereKey($key->suggestion_id)->firstOrFail();
                $suggestion->increment('occurrences_count', 1, $values);
            }
        }, 3);
    }
}
