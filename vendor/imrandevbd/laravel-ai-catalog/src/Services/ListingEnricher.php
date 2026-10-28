<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiCatalog\Image\ImageIntelligence;
use ImranDevBd\AiCatalog\Support\JsonLdBuilder;
use ImranDevBd\AiCatalog\Support\SpatialCommerceBuilder;

class ListingEnricher
{
    public function __construct(
        protected JsonLdBuilder $jsonLd = new JsonLdBuilder,
        protected ImageIntelligence $images = new ImageIntelligence,
        protected TranslationFanout $translations = new TranslationFanout,
        protected DppGenerator $dpp = new DppGenerator,
        protected SocialCommerceGenerator $social = new SocialCommerceGenerator,
        protected ReturnReductionEngine $returns = new ReturnReductionEngine,
        protected SpatialCommerceBuilder $spatial = new SpatialCommerceBuilder,
        protected SemanticEmbedder $embedder = new SemanticEmbedder,
    ) {}

    public function enrich(GeneratedListing $listing, GenerationInput $input): GeneratedListing
    {
        if ($listing->jsonLd === []) {
            $listing->jsonLd = $this->jsonLd->product($listing, $input->locale);
        }
        if ($listing->openGraph === []) {
            $listing->openGraph = $this->jsonLd->openGraph($listing);
        }

        if ($input->campaign && $listing->variants !== []) {
            foreach ($listing->variants as $i => $variant) {
                $listing->variants[$i]['campaign'] = $listing->variants[$i]['campaign'] ?? $input->campaign;
            }
        }

        if ($input->image) {
            $report = $this->images->analyze($input->image, $input);
            $listing->imageReport = $report;
            $listing->altText = $listing->altText ?: ($report['alt_text'] ?? null);
            $listing->ocrText = $listing->ocrText ?: ($report['ocr_text'] ?? null);
            $listing->lifestylePrompts = $listing->lifestylePrompts ?: ($report['lifestyle_prompts'] ?? []);
            $listing->flags = array_values(array_unique(array_merge($listing->flags, $report['flags'] ?? [])));
        }

        $listing = $this->translations->apply($listing, $input);

        // 2027 Enriched Features
        $withDpp = ($input->meta['dpp'] ?? null) ?? config('ai-catalog.dpp.enabled', true);
        if ($withDpp && $listing->dpp === []) {
            $listing->dpp = $this->dpp->generate($listing, $input);
        }

        $withSocial = ($input->meta['social'] ?? null) ?? config('ai-catalog.social_commerce.enabled', false);
        if ($withSocial && $listing->socialScripts === []) {
            $listing->socialScripts = $this->social->generate($listing, $input);
        }

        $withReturns = ($input->meta['return_reduction'] ?? null) ?? config('ai-catalog.returns_reduction.enabled', true);
        if ($withReturns && $listing->returnReduction === []) {
            $listing->returnReduction = $this->returns->generate($listing, $input);
        }

        $withSpatial = ($input->meta['spatial'] ?? null) ?? config('ai-catalog.spatial.enabled', false);
        if ($withSpatial && $listing->spatialPrompts === []) {
            $listing->spatialPrompts = $this->spatial->build($listing);
        }

        $withEmbeddings = ($input->meta['embeddings'] ?? null) ?? config('ai-catalog.embeddings.enabled', false);
        if ($withEmbeddings && $listing->embedding === null) {
            $listing->embedding = $this->embedder->embed($listing);
        }

        return $listing;
    }
}
