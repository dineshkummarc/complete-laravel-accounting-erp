<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiCatalog\Enums\InputMode;
use ImranDevBd\AiCatalog\Support\BrandVoiceRegistry;

class PromptBuilder
{
    public function __construct(
        protected BrandVoiceRegistry $voices = new BrandVoiceRegistry,
    ) {}

    public function system(GenerationInput $input): string
    {
        $override = config('ai-catalog.prompts.system');
        if (is_string($override) && $override !== '') {
            return $this->interpolate($override, $input);
        }

        $taxonomy = $this->taxonomyBlock($input);
        $specs = implode(', ', array_keys((array) config('ai-catalog.specs', [])));
        $voice = $this->voices->resolve($input->brandVoice);
        $tone = $input->tone->instruction();

        $lines = [
            'You are a senior ecommerce catalog copywriter and merchandiser.',
            'Return ONLY valid JSON matching the provided schema.',
            'Never invent a category that is not in the allowed taxonomy. If none fit, set suggested_category to null.',
            'Do not copy competitor or supplier wording. Write original, plagiarism-safe copy.',
            'Confidence scores must be between 0 and 1. Be honest: guesswork must score below 0.7.',
            "Write in locale: {$input->locale}.",
            "Tone: {$input->tone->value}. {$tone}",
            $specs !== '' ? "Populate specs keys when known: {$specs}. Use null when unknown — never fabricate measurements." : '',
            $voice ? "Brand voice: {$voice}" : '',
            $taxonomy,
        ];

        return implode("\n", array_filter($lines));
    }

    public function listing(GenerationInput $input): string
    {
        $key = match ($input->mode) {
            InputMode::Image => 'vision',
            InputMode::Url => 'url',
            default => 'listing',
        };

        $override = config("ai-catalog.prompts.{$key}");
        if (is_string($override) && $override !== '') {
            return $this->interpolate($override, $input);
        }

        $variantCount = max(0, min($input->variants, (int) config('ai-catalog.max_variants', 3)));

        $parts = [
            'Produce a complete, review-ready product listing.',
            'SEO title (max 70 chars), meta title (max 60), meta description (max 155).',
            'Short description: 1-2 sentences. Long description: 2-4 short paragraphs with features and benefits.',
            "Include {$variantCount} A/B variants with alternate title and short_description.",
            'Suggest 5-12 tags and 5-10 search_keywords.',
            'Add 3-5 FAQ pairs for the product page, care_instructions, shipping_notes, and hazard_flags when relevant.',
            'If size/fit matters, fill size_guide. Extract gtin/mpn/sku only when visible or explicit — never invent barcodes.',
        ];

        if ($input->campaign) {
            $parts[] = 'Campaign overlay: '.$input->campaign.'. Add a campaign-tagged variant, still honest.';
        }

        if ($input->mode === InputMode::Image) {
            $parts[] = 'Inspect the attached product photo. Extract name hints, color, material, condition, brand marks, and likely category.';
        }

        if ($input->mode === InputMode::Url) {
            $parts[] = 'Source URL: '.$input->url;
            $parts[] = 'Use the page facts (name, specs, materials) but rewrite every sentence uniquely.';
        }

        if ($input->mode === InputMode::Voice) {
            $parts[] = 'The input is dictated speech. Clean fillers, keep facts, structure a listing.';
        }

        if ($input->mode === InputMode::Document) {
            $parts[] = 'The input is a supplier datasheet. Prefer labeled specs over marketing adjectives.';
        }

        $seed = $input->promptSeed();
        if ($seed !== '') {
            $parts[] = 'Input: '.$seed;
        }

        if (is_array($input->onlyFields) && $input->onlyFields !== []) {
            $parts[] = 'Focus extra attention on fields: '.implode(', ', $input->onlyFields);
        }

        return implode("\n", $parts);
    }

    public function field(string $field, GeneratedListing $listing, GenerationInput $input): string
    {
        $override = config("ai-catalog.prompts.field.{$field}");
        if (is_string($override) && $override !== '') {
            return $this->interpolate($override, $input, ['field' => $field, 'listing' => json_encode($listing->toArray())]);
        }

        return implode("\n", [
            "Regenerate ONLY the \"{$field}\" field for this product listing.",
            'Keep every other fact consistent. Return JSON { "value": ..., "confidence": 0-1 }.',
            'Current listing JSON:',
            json_encode($listing->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Original input: '.$input->promptSeed(),
        ]);
    }

    /**
     * @param  array<string, string>  $extra
     */
    protected function interpolate(string $template, GenerationInput $input, array $extra = []): string
    {
        $taxonomy = implode(', ', array_map(fn ($row) => (string) ($row['name'] ?? ''), $input->taxonomy));

        $replacements = array_merge([
            '{{keyword}}' => (string) $input->keyword,
            '{{text}}' => (string) $input->text,
            '{{url}}' => (string) $input->url,
            '{{locale}}' => $input->locale,
            '{{tone}}' => $input->tone->value,
            '{{brand_voice}}' => (string) $input->brandVoice,
            '{{taxonomy}}' => $taxonomy,
            '{{seed}}' => $input->promptSeed(),
        ], array_combine(
            array_map(fn ($k) => '{{'.$k.'}}', array_keys($extra)),
            array_values($extra)
        ) ?: []);

        return strtr($template, $replacements);
    }

    protected function taxonomyBlock(GenerationInput $input): string
    {
        if ($input->taxonomy === []) {
            return 'No store taxonomy supplied. Leave suggested_category null rather than inventing one.';
        }

        $lines = [];
        foreach ($input->taxonomy as $row) {
            $name = (string) ($row['name'] ?? '');
            $id = $row['id'] ?? '';
            $parent = $row['parent_id'] ?? null;
            $lines[] = $parent ? "- {$name} (id: {$id}, parent: {$parent})" : "- {$name} (id: {$id})";
        }

        return "Allowed categories (match names exactly):\n".implode("\n", $lines);
    }
}
