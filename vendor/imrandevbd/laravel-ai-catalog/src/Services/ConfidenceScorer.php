<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;

class ConfidenceScorer
{
    public function enrich(GeneratedListing $listing): GeneratedListing
    {
        $scores = $listing->confidence;

        $defaults = [
            'title' => $this->textScore($listing->title, 8, 80),
            'meta_title' => $this->textScore($listing->metaTitle, 8, 70),
            'meta_description' => $this->textScore($listing->metaDescription, 40, 170),
            'short_description' => $this->textScore($listing->shortDescription, 40, 400),
            'long_description' => $this->textScore($listing->longDescription, 120, 4000),
            'specs' => $this->specsScore($listing->specs),
            'tags' => $this->tagsScore($listing->tags),
            'category' => $listing->categoryId === null ? 0.35 : 0.9,
        ];

        foreach ($defaults as $field => $heuristic) {
            $modelScore = $scores[$field] ?? null;
            $scores[$field] = $modelScore === null
                ? $heuristic
                : round(min((float) $modelScore, $heuristic + 0.15), 4);
        }

        if ($listing->categoryId === null) {
            $scores['category'] = min($scores['category'] ?? 1, 0.4);
        }

        $listing->confidence = $scores;

        return $listing;
    }

    protected function textScore(string $value, int $min, int $max): float
    {
        $len = mb_strlen(trim($value));
        if ($len === 0) {
            return 0.1;
        }

        if ($len < $min) {
            return round(max(0.25, $len / max($min, 1) * 0.6), 4);
        }

        if ($len > $max) {
            return 0.55;
        }

        return 0.86;
    }

    /**
     * @param  array<string, mixed>  $specs
     */
    protected function specsScore(array $specs): float
    {
        $defined = array_filter($specs, fn ($v) => $v !== null && $v !== '');
        $keys = max(count($specs), 1);

        return round(0.4 + (count($defined) / $keys) * 0.5, 4);
    }

    /**
     * @param  list<string>  $tags
     */
    protected function tagsScore(array $tags): float
    {
        $count = count($tags);
        if ($count === 0) {
            return 0.2;
        }

        if ($count < 3) {
            return 0.55;
        }

        return min(0.92, 0.7 + ($count * 0.03));
    }
}
