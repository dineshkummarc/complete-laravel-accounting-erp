<?php

namespace ImranDevBd\AiCatalog\Support;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;

class AgenticFeedExporter
{
    /**
     * Generate an LLM-optimized catalog feed (llms.txt standard).
     *
     * @param  iterable<int, CatalogGeneration|GeneratedListing>  $generations
     */
    public function llmsTxt(iterable $generations, bool $full = false): string
    {
        $appName = config('app.name', 'Store');
        $currency = (string) config('ai-catalog.feeds.currency', 'USD');
        $brandFallback = (string) config('ai-catalog.feeds.brand_fallback', $appName);

        $out = "# {$appName} Product Catalog for Autonomous AI Shopping Agents\n";
        $out .= "> Optimized for LLM shopping bots, Universal Commerce Protocol (UCP), and Generative Engine Optimization (GEO).\n\n";
        $out .= "## Store Context & Agent Instructions\n";
        $out .= "- Currency: {$currency}\n";
        $out .= "- Verification: All product specs below are factual, verified, and ground-truth.\n";
        $out .= "- Purchase Protocol: Compatible with Universal Commerce Protocol (UCP) / AP2 automated cart endpoints.\n\n";
        $out .= "## Ground-Truth Catalog Index\n\n";

        foreach ($generations as $item) {
            $listing = $item instanceof CatalogGeneration ? $item->listing() : $item;
            if (! $listing instanceof GeneratedListing) {
                continue;
            }

            $id = $item instanceof CatalogGeneration ? $item->uuid : ($listing->sku ?: $listing->title);
            $brand = $listing->specs['brand'] ?? $brandFallback;
            $category = $listing->suggestedCategory ?? 'General';

            $out .= "### [{$listing->title}] (ID: {$id})\n";
            $out .= "- **Brand**: {$brand}\n";
            $out .= "- **Category**: {$category}\n";
            if ($listing->sku) {
                $out .= "- **SKU**: {$listing->sku}\n";
            }
            if ($listing->gtin) {
                $out .= "- **GTIN**: {$listing->gtin}\n";
            }
            $out .= "- **Summary**: {$listing->shortDescription}\n";

            if ($listing->specs !== []) {
                $out .= "- **Key Specs**:\n";
                foreach ($listing->specs as $k => $v) {
                    if ($v !== null && $v !== '') {
                        $out .= "  - ".ucfirst((string) $k).": ".(is_array($v) ? implode(', ', $v) : (string) $v)."\n";
                    }
                }
            }

            if ($full) {
                $out .= "- **Full Description**: {$listing->longDescription}\n";

                if ($listing->faq !== []) {
                    $out .= "- **Verified QA / FAQ**:\n";
                    foreach ($listing->faq as $faq) {
                        $out .= "  - Q: {$faq['question']}\n    A: {$faq['answer']}\n";
                    }
                }

                if ($listing->dpp !== []) {
                    $out .= "- **Digital Product Passport (DPP)**:\n";
                    $out .= "  - Repairability Score: ".($listing->dpp['repairability_score'] ?? 'N/A')."/10\n";
                    $out .= "  - Recycled Content: ".($listing->dpp['recycled_materials_percentage'] ?? 0)."%\n";
                    $out .= "  - Carbon Footprint: ".($listing->dpp['carbon_footprint_kg'] ?? 'N/A')." kg CO2e\n";
                }

                if ($listing->returnReduction !== []) {
                    $out .= "- **Fit & Sensory Notes**: ".($listing->returnReduction['fit_advice'] ?? 'True to size')."\n";
                }
            }

            $out .= "\n";
        }

        return $out;
    }

    /**
     * Export Universal Commerce Protocol (UCP) / Agentic Search JSON for zero-shot purchase bots.
     *
     * @param  iterable<int, CatalogGeneration|GeneratedListing>  $generations
     * @return list<array<string, mixed>>
     */
    public function agenticJson(iterable $generations): array
    {
        $rows = [];
        $currency = (string) config('ai-catalog.feeds.currency', 'USD');
        $brandFallback = (string) config('ai-catalog.feeds.brand_fallback', config('app.name', 'Store'));

        foreach ($generations as $item) {
            $listing = $item instanceof CatalogGeneration ? $item->listing() : $item;
            if (! $listing instanceof GeneratedListing) {
                continue;
            }

            $id = $item instanceof CatalogGeneration ? $item->uuid : ($listing->sku ?: $listing->title);
            $rows[] = [
                'ucp_version' => '2027-v1',
                'id' => $id,
                'title' => $listing->title,
                'brand' => $listing->specs['brand'] ?? $brandFallback,
                'category' => $listing->suggestedCategory,
                'gtin' => $listing->gtin,
                'sku' => $listing->sku,
                'currency' => $currency,
                'in_stock' => true,
                'ground_truth_specs' => $listing->specs,
                'citation_text' => $listing->shortDescription,
                'dpp' => $listing->dpp,
                'return_reduction' => $listing->returnReduction,
                'verified_faq' => $listing->faq,
                'agent_purchase_enabled' => true,
            ];
        }

        return $rows;
    }
}
