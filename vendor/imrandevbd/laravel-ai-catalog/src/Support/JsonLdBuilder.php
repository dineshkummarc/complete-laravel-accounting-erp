<?php

namespace ImranDevBd\AiCatalog\Support;

use ImranDevBd\AiCatalog\Data\GeneratedListing;

class JsonLdBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function product(GeneratedListing $listing, string $locale = 'en'): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $listing->title,
            'description' => $listing->shortDescription ?: $listing->longDescription,
            'brand' => isset($listing->specs['brand']) ? [
                '@type' => 'Brand',
                'name' => $listing->specs['brand'],
            ] : null,
            'sku' => $listing->sku,
            'gtin' => $listing->gtin,
            'mpn' => $listing->mpn,
            'color' => $listing->specs['color'] ?? null,
            'material' => $listing->specs['materials'] ?? null,
            'category' => $listing->suggestedCategory,
            'inLanguage' => $locale,
            'keywords' => implode(', ', $listing->tags),
        ], fn ($v) => $v !== null && $v !== '');
    }

    /**
     * @return array<string, mixed>
     */
    public function openGraph(GeneratedListing $listing): array
    {
        return array_filter([
            'og:type' => 'product',
            'og:title' => $listing->metaTitle ?: $listing->title,
            'og:description' => $listing->metaDescription ?: $listing->shortDescription,
        ]);
    }
}
