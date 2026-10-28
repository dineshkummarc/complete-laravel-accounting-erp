<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiCatalog\Support\ListingSchema;

class TranslationFanout
{
    public function __construct(
        protected HubClient $hub = new HubClient,
    ) {}

    public function apply(GeneratedListing $listing, GenerationInput $input): GeneratedListing
    {
        if (! config('ai-catalog.i18n.fanout', true)) {
            return $listing;
        }

        $locales = $input->extraLocales();
        if ($locales === []) {
            return $listing;
        }

        $glossary = (array) config('ai-catalog.i18n.glossary', []);
        $translations = $listing->translations;

        foreach ($locales as $locale) {
            try {
                $prompt = implode("\n", [
                    "Translate this product listing into locale {$locale}.",
                    'Keep measurements, brand names, and model codes unchanged.',
                    'Honor this glossary JSON: '.json_encode($glossary[$locale] ?? $glossary),
                    'Source JSON: '.json_encode([
                        'title' => $listing->title,
                        'short_description' => $listing->shortDescription,
                        'long_description' => $listing->longDescription,
                        'meta_title' => $listing->metaTitle,
                        'meta_description' => $listing->metaDescription,
                        'tags' => $listing->tags,
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                $response = $this->hub->complete(
                    ListingSchema::translation(),
                    'You are a catalog localizer. Return JSON only. Do not invent specs.',
                    $prompt,
                    $input,
                    'ai-catalog.translate',
                );
                $row = $response->json();
                if (is_array($row)) {
                    $row['locale'] = $locale;
                    if (in_array($locale, (array) config('ai-catalog.i18n.rtl', []), true)) {
                        $row['rtl'] = true;
                    }
                    $sourceLen = max(mb_strlen($listing->longDescription), 1);
                    $exp = mb_strlen((string) ($row['long_description'] ?? '')) / $sourceLen;
                    if ($exp > (float) config('ai-catalog.i18n.expansion_warn', 1.4)) {
                        $row['expansion_warning'] = round($exp, 2);
                    }
                    $translations[$locale] = $row;
                }
            } catch (\Throwable) {
                $translations[$locale] = ['locale' => $locale, 'error' => true];
            }
        }

        $listing->translations = $translations;

        return $listing;
    }
}
