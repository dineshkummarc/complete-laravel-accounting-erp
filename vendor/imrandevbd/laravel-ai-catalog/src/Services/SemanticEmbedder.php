<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiHub\Facades\AIHub;

class SemanticEmbedder
{
    /**
     * Generate a vector embedding for a listing (title, description, specs, keywords).
     *
     * @return list<float>
     */
    public function embed(GeneratedListing $listing): array
    {
        $text = $this->buildEmbeddingText($listing);

        try {
            $provider = config('ai-catalog.provider');
            $embedModel = config('ai-catalog.embed_model');

            $request = $provider ? AIHub::provider((string) $provider) : AIHub::provider();
            if ($embedModel) {
                $request->model($embedModel);
            }

            $response = $request->embed($text);
            $vector = $response->first();

            if ($vector !== []) {
                return array_values(array_map('floatval', $vector));
            }
        } catch (\Throwable) {
            // Fallback for offline, testbench or unconfigured provider
        }

        return $this->deterministicFallbackVector($text);
    }

    public function buildEmbeddingText(GeneratedListing $listing): string
    {
        $parts = [
            $listing->title,
            $listing->shortDescription,
            $listing->suggestedCategory,
            implode(' ', $listing->tags),
            implode(' ', $listing->searchKeywords),
        ];

        foreach ($listing->specs as $k => $v) {
            if ($v !== null && $v !== '') {
                $parts[] = "{$k}: ".(is_array($v) ? implode(' ', $v) : $v);
            }
        }

        return trim(implode('. ', array_filter($parts)));
    }

    /**
     * Deterministic normalized pseudo-vector fallback (length 16) for tests/offline.
     *
     * @return list<float>
     */
    protected function deterministicFallbackVector(string $text): array
    {
        $hash = hash('sha256', $text);
        $vector = [];
        for ($i = 0; $i < 16; $i++) {
            $chunk = substr($hash, $i * 4, 4);
            $val = (hexdec($chunk) / 65535.0) * 2.0 - 1.0;
            $vector[] = round($val, 6);
        }

        return $vector;
    }
}
