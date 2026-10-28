<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use Illuminate\Support\Str;

class DuplicateChecker
{
    /**
     * @return list<string>
     */
    public function flags(GeneratedListing $listing): array
    {
        $flags = [];
        $threshold = (float) config('ai-catalog.quality.duplicate_title_threshold', 85);
        $modelClass = config('ai-catalog.schema.model');
        $titleColumn = (string) (config('ai-catalog.schema.map.title') ?: 'name');

        if (! is_string($modelClass) || ! class_exists($modelClass) || $listing->title === '') {
            return $flags;
        }

        try {
            $existing = $modelClass::query()
                ->select($titleColumn)
                ->limit(200)
                ->pluck($titleColumn);
        } catch (\Throwable) {
            return $flags;
        }

        $needle = Str::lower($listing->title);
        foreach ($existing as $title) {
            if (! is_string($title) || $title === '') {
                continue;
            }
            similar_text($needle, Str::lower($title), $percent);
            if ($percent >= $threshold) {
                $flags[] = 'possible_duplicate_title';
                break;
            }
        }

        return $flags;
    }
}
