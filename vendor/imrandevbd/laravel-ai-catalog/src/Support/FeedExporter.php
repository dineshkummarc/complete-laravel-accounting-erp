<?php

namespace ImranDevBd\AiCatalog\Support;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;

class FeedExporter
{
    /**
     * @param  iterable<int, CatalogGeneration>  $generations
     * @return list<array<string, mixed>>
     */
    public function google(iterable $generations): array
    {
        $rows = [];
        $currency = (string) config('ai-catalog.feeds.currency', 'USD');
        $brandFallback = (string) config('ai-catalog.feeds.brand_fallback', '');

        foreach ($generations as $generation) {
            $listing = $generation instanceof CatalogGeneration ? $generation->listing() : $generation;
            if (! $listing instanceof GeneratedListing) {
                continue;
            }
            $rows[] = [
                'id' => $generation instanceof CatalogGeneration ? $generation->uuid : ($listing->sku ?: $listing->title),
                'title' => $listing->title,
                'description' => $listing->longDescription ?: $listing->shortDescription,
                'availability' => 'in stock',
                'condition' => $listing->specs['condition'] ?? config('ai-catalog.feeds.google_condition', 'new'),
                'brand' => $listing->specs['brand'] ?? $brandFallback,
                'gtin' => $listing->gtin,
                'mpn' => $listing->mpn,
                'color' => $listing->specs['color'] ?? null,
                'google_product_category' => $listing->suggestedCategory,
                'currency' => $currency,
            ];
        }

        return $rows;
    }

    /**
     * @param  iterable<int, CatalogGeneration>  $generations
     */
    public function facebookCsv(iterable $generations): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['id', 'title', 'description', 'availability', 'condition', 'brand', 'gtin']);
        foreach ($this->google($generations) as $row) {
            fputcsv($handle, [
                $row['id'], $row['title'], $row['description'],
                $row['availability'], $row['condition'], $row['brand'], $row['gtin'],
            ]);
        }
        rewind($handle);
        $csv = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $csv;
    }
}
