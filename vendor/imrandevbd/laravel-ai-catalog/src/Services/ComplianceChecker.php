<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use Illuminate\Support\Str;

class ComplianceChecker
{
    /**
     * @return list<string>
     */
    public function flags(GeneratedListing $listing, ?string $region = null): array
    {
        if (! config('ai-catalog.quality.flag_restricted', true)) {
            return [];
        }

        $words = $this->words($region);
        if ($words === []) {
            return [];
        }

        $haystack = Str::lower(implode(' ', [
            $listing->title,
            $listing->metaTitle,
            $listing->metaDescription,
            $listing->shortDescription,
            $listing->longDescription,
            $listing->careInstructions,
            implode(' ', $listing->tags),
            implode(' ', array_map(fn ($f) => ($f['question'] ?? '').' '.($f['answer'] ?? ''), $listing->faq)),
        ]));

        $hits = [];
        foreach ($words as $word) {
            $needle = Str::lower((string) $word);
            if ($needle !== '' && str_contains($haystack, $needle)) {
                $hits[] = 'restricted:'.$needle;
            }
        }

        foreach ($listing->hazardFlags as $hazard) {
            $hits[] = 'hazard:'.Str::slug((string) $hazard);
        }

        return array_values(array_unique($hits));
    }

    /**
     * @return list<string>
     */
    public function words(?string $region = null): array
    {
        $region = strtolower((string) ($region ?: config('ai-catalog.quality.region', 'global')));
        $packs = (array) config('ai-catalog.quality.packs', []);
        $fromPack = $packs[$region] ?? $packs['global'] ?? [];
        $custom = (array) config('ai-catalog.quality.restricted_words', []);

        return array_values(array_unique(array_filter(array_merge($fromPack, $custom))));
    }
}
