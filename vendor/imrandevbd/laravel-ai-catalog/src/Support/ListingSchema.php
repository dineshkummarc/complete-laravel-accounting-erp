<?php

namespace ImranDevBd\AiCatalog\Support;

class ListingSchema
{
    /**
     * @return array<string, mixed>
     */
    public static function listing(): array
    {
        $specProperties = [];
        foreach ((array) config('ai-catalog.specs', []) as $key => $type) {
            $specProperties[(string) $key] = ['type' => ['string', 'null']];
        }

        if ($specProperties === []) {
            $specProperties = [
                'size' => ['type' => ['string', 'null']],
                'weight' => ['type' => ['string', 'null']],
                'color' => ['type' => ['string', 'null']],
                'brand' => ['type' => ['string', 'null']],
            ];
        }

        return [
            'type' => 'object',
            'additionalProperties' => true,
            'required' => [
                'title',
                'meta_title',
                'meta_description',
                'short_description',
                'long_description',
                'specs',
                'tags',
                'suggested_category',
                'suggested_subcategory',
                'confidence',
                'variants',
            ],
            'properties' => [
                'title' => ['type' => 'string'],
                'meta_title' => ['type' => 'string'],
                'meta_description' => ['type' => 'string'],
                'short_description' => ['type' => 'string'],
                'long_description' => ['type' => 'string'],
                'specs' => [
                    'type' => 'object',
                    'additionalProperties' => true,
                    'properties' => $specProperties,
                ],
                'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
                'search_keywords' => ['type' => 'array', 'items' => ['type' => 'string']],
                'suggested_category' => ['type' => ['string', 'null']],
                'suggested_subcategory' => ['type' => ['string', 'null']],
                'faq' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'question' => ['type' => 'string'],
                            'answer' => ['type' => 'string'],
                        ],
                    ],
                ],
                'care_instructions' => ['type' => ['string', 'null']],
                'size_guide' => ['type' => ['object', 'array', 'null']],
                'shipping_notes' => ['type' => ['string', 'null']],
                'hazard_flags' => ['type' => 'array', 'items' => ['type' => 'string']],
                'gtin' => ['type' => ['string', 'null']],
                'mpn' => ['type' => ['string', 'null']],
                'sku' => ['type' => ['string', 'null']],
                'confidence' => [
                    'type' => 'object',
                    'additionalProperties' => ['type' => 'number'],
                    'properties' => [
                        'title' => ['type' => 'number'],
                        'meta_title' => ['type' => 'number'],
                        'meta_description' => ['type' => 'number'],
                        'short_description' => ['type' => 'number'],
                        'long_description' => ['type' => 'number'],
                        'specs' => ['type' => 'number'],
                        'tags' => ['type' => 'number'],
                        'category' => ['type' => 'number'],
                    ],
                ],
                'variants' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => true,
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'short_description' => ['type' => 'string'],
                            'meta_title' => ['type' => 'string'],
                            'campaign' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function field(string $field): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['value', 'confidence'],
            'properties' => [
                'value' => ['type' => ['string', 'object', 'array', 'null']],
                'confidence' => ['type' => 'number'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function translation(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => true,
            'required' => ['locale', 'title', 'short_description', 'long_description', 'meta_title', 'meta_description'],
            'properties' => [
                'locale' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'short_description' => ['type' => 'string'],
                'long_description' => ['type' => 'string'],
                'meta_title' => ['type' => 'string'],
                'meta_description' => ['type' => 'string'],
                'tags' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function altText(): array
    {
        return [
            'type' => 'object',
            'required' => ['alt_text'],
            'properties' => [
                'alt_text' => ['type' => 'string'],
                'ocr_text' => ['type' => ['string', 'null']],
            ],
        ];
    }
}
