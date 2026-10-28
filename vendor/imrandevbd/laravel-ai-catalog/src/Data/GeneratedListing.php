<?php

namespace ImranDevBd\AiCatalog\Data;

class GeneratedListing
{
    /**
     * @param  array<string, mixed>  $specs
     * @param  list<string>  $tags
     * @param  array<string, float>  $confidence
     * @param  list<array<string, mixed>>  $variants
     * @param  list<string>  $flags
     * @param  list<array{question: string, answer: string}>  $faq
     * @param  array<string, mixed>  $translations
     * @param  array<string, mixed>  $jsonLd
     * @param  array<string, mixed>  $openGraph
     * @param  array<string, mixed>  $imageReport
     * @param  list<string>  $lifestylePrompts
     * @param  list<string>  $searchKeywords
     * @param  array<string, mixed>  $dpp
     * @param  array<string, mixed>  $socialScripts
     * @param  array<string, mixed>  $returnReduction
     * @param  array<string, mixed>  $spatialPrompts
     * @param  list<float>|null  $embedding
     */
    public function __construct(
        public string $title = '',
        public string $metaTitle = '',
        public string $metaDescription = '',
        public string $shortDescription = '',
        public string $longDescription = '',
        public array $specs = [],
        public array $tags = [],
        public ?string $suggestedCategory = null,
        public ?string $suggestedSubcategory = null,
        public int|string|null $categoryId = null,
        public int|string|null $subcategoryId = null,
        public array $confidence = [],
        public array $variants = [],
        public array $flags = [],
        public array $faq = [],
        public string $careInstructions = '',
        public array $sizeGuide = [],
        public string $shippingNotes = '',
        public array $hazardFlags = [],
        public ?string $gtin = null,
        public ?string $mpn = null,
        public ?string $sku = null,
        public ?string $altText = null,
        public ?string $ocrText = null,
        public array $jsonLd = [],
        public array $openGraph = [],
        public array $translations = [],
        public array $imageReport = [],
        public array $lifestylePrompts = [],
        public array $searchKeywords = [],
        public array $dpp = [],
        public array $socialScripts = [],
        public array $returnReduction = [],
        public array $spatialPrompts = [],
        public ?array $embedding = null,
        public array $raw = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $specs = is_array($data['specs'] ?? null) ? $data['specs'] : [];
        $tags = is_array($data['tags'] ?? null) ? array_values(array_filter($data['tags'], 'is_string')) : [];
        $confidence = [];
        foreach ((array) ($data['confidence'] ?? []) as $field => $score) {
            $confidence[(string) $field] = round((float) $score, 4);
        }

        $variants = [];
        foreach ((array) ($data['variants'] ?? []) as $variant) {
            if (is_array($variant)) {
                $variants[] = $variant;
            }
        }

        $faq = [];
        foreach ((array) ($data['faq'] ?? []) as $row) {
            if (is_array($row) && isset($row['question'], $row['answer'])) {
                $faq[] = ['question' => (string) $row['question'], 'answer' => (string) $row['answer']];
            }
        }

        return new self(
            title: (string) ($data['title'] ?? ''),
            metaTitle: (string) ($data['meta_title'] ?? $data['metaTitle'] ?? ''),
            metaDescription: (string) ($data['meta_description'] ?? $data['metaDescription'] ?? ''),
            shortDescription: (string) ($data['short_description'] ?? $data['shortDescription'] ?? ''),
            longDescription: (string) ($data['long_description'] ?? $data['longDescription'] ?? ''),
            specs: $specs,
            tags: $tags,
            suggestedCategory: isset($data['suggested_category']) ? (string) $data['suggested_category'] : ($data['suggestedCategory'] ?? null),
            suggestedSubcategory: isset($data['suggested_subcategory']) ? (string) $data['suggested_subcategory'] : ($data['suggestedSubcategory'] ?? null),
            categoryId: $data['category_id'] ?? $data['categoryId'] ?? null,
            subcategoryId: $data['subcategory_id'] ?? $data['subcategoryId'] ?? null,
            confidence: $confidence,
            variants: $variants,
            flags: array_values(array_filter((array) ($data['flags'] ?? []), 'is_string')),
            faq: $faq,
            careInstructions: (string) ($data['care_instructions'] ?? $data['careInstructions'] ?? ''),
            sizeGuide: is_array($data['size_guide'] ?? null) ? $data['size_guide'] : (is_array($data['sizeGuide'] ?? null) ? $data['sizeGuide'] : []),
            shippingNotes: (string) ($data['shipping_notes'] ?? $data['shippingNotes'] ?? ''),
            hazardFlags: array_values(array_filter((array) ($data['hazard_flags'] ?? $data['hazardFlags'] ?? []), 'is_string')),
            gtin: isset($data['gtin']) ? (string) $data['gtin'] : null,
            mpn: isset($data['mpn']) ? (string) $data['mpn'] : null,
            sku: isset($data['sku']) ? (string) $data['sku'] : null,
            altText: isset($data['alt_text']) ? (string) $data['alt_text'] : ($data['altText'] ?? null),
            ocrText: isset($data['ocr_text']) ? (string) $data['ocr_text'] : ($data['ocrText'] ?? null),
            jsonLd: is_array($data['json_ld'] ?? null) ? $data['json_ld'] : (is_array($data['jsonLd'] ?? null) ? $data['jsonLd'] : []),
            openGraph: is_array($data['open_graph'] ?? null) ? $data['open_graph'] : (is_array($data['openGraph'] ?? null) ? $data['openGraph'] : []),
            translations: is_array($data['translations'] ?? null) ? $data['translations'] : [],
            imageReport: is_array($data['image_report'] ?? null) ? $data['image_report'] : (is_array($data['imageReport'] ?? null) ? $data['imageReport'] : []),
            lifestylePrompts: array_values(array_filter((array) ($data['lifestyle_prompts'] ?? $data['lifestylePrompts'] ?? []), 'is_string')),
            searchKeywords: array_values(array_filter((array) ($data['search_keywords'] ?? $data['searchKeywords'] ?? []), 'is_string')),
            dpp: is_array($data['dpp'] ?? null) ? $data['dpp'] : [],
            socialScripts: is_array($data['social_scripts'] ?? null) ? $data['social_scripts'] : (is_array($data['socialScripts'] ?? null) ? $data['socialScripts'] : []),
            returnReduction: is_array($data['return_reduction'] ?? null) ? $data['return_reduction'] : (is_array($data['returnReduction'] ?? null) ? $data['returnReduction'] : []),
            spatialPrompts: is_array($data['spatial_prompts'] ?? null) ? $data['spatial_prompts'] : (is_array($data['spatialPrompts'] ?? null) ? $data['spatialPrompts'] : []),
            embedding: is_array($data['embedding'] ?? null) ? array_values(array_map('floatval', $data['embedding'])) : null,
            raw: $data,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'short_description' => $this->shortDescription,
            'long_description' => $this->longDescription,
            'specs' => $this->specs,
            'tags' => $this->tags,
            'suggested_category' => $this->suggestedCategory,
            'suggested_subcategory' => $this->suggestedSubcategory,
            'category_id' => $this->categoryId,
            'subcategory_id' => $this->subcategoryId,
            'confidence' => $this->confidence,
            'variants' => $this->variants,
            'flags' => $this->flags,
            'faq' => $this->faq,
            'care_instructions' => $this->careInstructions,
            'size_guide' => $this->sizeGuide,
            'shipping_notes' => $this->shippingNotes,
            'hazard_flags' => $this->hazardFlags,
            'gtin' => $this->gtin,
            'mpn' => $this->mpn,
            'sku' => $this->sku,
            'alt_text' => $this->altText,
            'ocr_text' => $this->ocrText,
            'json_ld' => $this->jsonLd,
            'open_graph' => $this->openGraph,
            'translations' => $this->translations,
            'image_report' => $this->imageReport,
            'lifestyle_prompts' => $this->lifestylePrompts,
            'search_keywords' => $this->searchKeywords,
            'dpp' => $this->dpp,
            'social_scripts' => $this->socialScripts,
            'return_reduction' => $this->returnReduction,
            'spatial_prompts' => $this->spatialPrompts,
            'embedding' => $this->embedding,
        ];
    }

    public function overallConfidence(): float
    {
        if ($this->confidence === []) {
            return 0.0;
        }

        return round(array_sum($this->confidence) / count($this->confidence), 4);
    }

    /**
     * @return array<string, float>
     */
    public function lowConfidenceFields(float $threshold): array
    {
        return array_filter($this->confidence, fn (float $score) => $score < $threshold);
    }
}
