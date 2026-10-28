<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Data\GenerationInput;

class ReturnReductionEngine
{
    /**
     * Generate return-reduction advisories, tactile handfeel notes, and proactive mitigation copy.
     *
     * @return array<string, mixed>
     */
    public function generate(GeneratedListing $listing, ?GenerationInput $input = null): array
    {
        $category = strtolower((string) ($listing->suggestedCategory ?? ''));
        $materials = strtolower((string) ($listing->specs['materials'] ?? $listing->specs['material'] ?? ''));
        $size = strtolower((string) ($listing->specs['size'] ?? ''));

        $fitTendency = 'true_to_size';
        $fitAdvice = 'Fits true to standard sizing. Order your usual size.';

        if (str_contains($materials, 'wool') || str_contains($materials, 'linen') || str_contains($category, 'shoe') || str_contains($category, 'footwear')) {
            $fitTendency = 'structured_snug';
            $fitAdvice = 'Structured fit. If you prefer a relaxed feel or wear thick socks/layers, consider sizing up one half size.';
        } elseif (str_contains($materials, 'spandex') || str_contains($materials, 'elastane') || str_contains($materials, 'stretch')) {
            $fitTendency = 'adaptive_stretch';
            $fitAdvice = 'Contains flexible stretch. Conforms comfortably to body contours without feeling tight.';
        }

        $tactileFeel = $this->estimateTactileFeel($materials);
        $riskScore = $this->calculateReturnRisk($category, $materials);

        $mitigationFaqs = [
            [
                'question' => 'How does the sizing run, and what if it doesn’t fit?',
                'answer' => "{$fitAdvice} We offer easy, hassle-free exchanges if you need an alternate size.",
            ],
            [
                'question' => 'What does the material actually feel like in person?',
                'answer' => "{$tactileFeel} It is designed to match the photos accurately in natural ambient light.",
            ],
        ];

        return [
            'return_risk_score' => $riskScore, // 0 - 100
            'fit_tendency' => $fitTendency,
            'fit_advice' => $fitAdvice,
            'tactile_expectations' => $tactileFeel,
            'preemptive_faqs' => $mitigationFaqs,
            'confidence' => 0.88,
        ];
    }

    protected function estimateTactileFeel(string $materials): string
    {
        if (str_contains($materials, 'ceramic')) {
            return 'Substantial, smooth matte handfeel with satisfying thermal retention and weighted stability.';
        }
        if (str_contains($materials, 'linen') || str_contains($materials, 'flax')) {
            return 'Airy, breathable textured weave that softens naturally with each wash; intentional relaxed slub texture.';
        }
        if (str_contains($materials, 'leather')) {
            return 'Supple, full-grain leather feel with a subtle satin sheen that develops a rich patina over time.';
        }
        if (str_contains($materials, 'plastic') || str_contains($materials, 'silicone')) {
            return 'Silky soft-touch matte finish, non-slip grip, and lightweight ergonomic feel.';
        }

        return 'Soft, durable, and premium handfeel with balanced weight and texture.';
    }

    protected function calculateReturnRisk(string $category, string $materials): int
    {
        $cat = strtolower($category);
        if (str_contains($cat, 'shoe') || str_contains($cat, 'footwear') || str_contains($cat, 'dress') || str_contains($cat, 'swim')) {
            return 28; // High return category
        }
        if (str_contains($cat, 'apparel') || str_contains($cat, 'clothing')) {
            return 22;
        }
        if (str_contains($cat, 'electronic') || str_contains($cat, 'gadget')) {
            return 14;
        }

        return 8; // Low return risk (home goods, accessories, books)
    }
}
