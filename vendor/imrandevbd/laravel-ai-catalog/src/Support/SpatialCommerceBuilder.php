<?php

namespace ImranDevBd\AiCatalog\Support;

use ImranDevBd\AiCatalog\Data\GeneratedListing;

class SpatialCommerceBuilder
{
    /**
     * Build spatial commerce 3D/AR cues, PBR material specs, and multi-angle turntable prompts.
     *
     * @return array<string, mixed>
     */
    public function build(GeneratedListing $listing): array
    {
        $dimensions = (string) ($listing->specs['dimensions'] ?? $listing->specs['size'] ?? '20x15x10 cm');
        $materials = (string) ($listing->specs['materials'] ?? $listing->specs['material'] ?? 'composite');
        $color = (string) ($listing->specs['color'] ?? 'neutral');
        $title = $listing->title;

        $pbrSpecs = $this->calculatePbrSpecs($materials);
        $boundingBox = $this->parseBoundingBox($dimensions);

        return [
            'spatial_standard' => 'USDZ-glTF-2027',
            'bounding_box_cm' => $boundingBox,
            'pbr_material_channels' => $pbrSpecs,
            'ar_anchor_type' => $this->determineAnchorType($listing),
            'turntable_render_prompts' => [
                'hero_frontal' => "Studio hero shot of {$title}, {$color}, {$materials}, clean pure seamless cyclorama lighting, 8k render, photorealistic product photography, 85mm lens f/4.",
                'isometric_45' => "Three-quarters 45-degree angle isometric view of {$title}, showcasing depth and tactile texture, soft rim-lighting, hyper-detailed.",
                'macro_detail' => "Extreme macro close-up of {$title} showing authentic {$materials} surface grain, micro-bevels, and refined finishing.",
                'spatial_context' => "Apple Vision Pro spatial scene: {$title} placed naturally on a modern minimalist oak desk or living space, ambient raytraced occlusion shadows.",
            ],
            'vision_pro_ready' => true,
        ];
    }

    /**
     * @return array{width: float, height: float, depth: float, unit: string}
     */
    protected function parseBoundingBox(string $dimensions): array
    {
        if (preg_match('/([\d\.]+)\s*[xX*×]\s*([\d\.]+)\s*[xX*×]\s*([\d\.]+)/', $dimensions, $matches)) {
            return [
                'width' => (float) $matches[1],
                'height' => (float) $matches[2],
                'depth' => (float) $matches[3],
                'unit' => 'cm',
            ];
        }

        return [
            'width' => 20.0,
            'height' => 15.0,
            'depth' => 10.0,
            'unit' => 'cm',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function calculatePbrSpecs(string $materials): array
    {
        $mat = strtolower($materials);
        if (str_contains($mat, 'metal') || str_contains($mat, 'steel') || str_contains($mat, 'aluminum')) {
            return [
                'metallic' => 0.9,
                'roughness' => 0.25,
                'sheen' => 0.1,
                'subsurface' => 0.0,
                'normal_texture_cue' => 'Brushed metal anisotropic grain or bead-blasted satin texture.',
            ];
        }

        if (str_contains($mat, 'glass') || str_contains($mat, 'crystal')) {
            return [
                'metallic' => 0.0,
                'roughness' => 0.05,
                'transmission' => 0.95,
                'ior' => 1.52,
                'normal_texture_cue' => 'Flawless optical glass smoothness with subtle Fresnel edge glow.',
            ];
        }

        if (str_contains($mat, 'ceramic')) {
            return [
                'metallic' => 0.0,
                'roughness' => 0.45,
                'sheen' => 0.3,
                'subsurface' => 0.15,
                'normal_texture_cue' => 'Silky matte glazed ceramic with soft micro-porosity.',
            ];
        }

        if (str_contains($mat, 'linen') || str_contains($mat, 'fabric') || str_contains($mat, 'cotton')) {
            return [
                'metallic' => 0.0,
                'roughness' => 0.85,
                'sheen' => 0.6,
                'subsurface' => 0.2,
                'normal_texture_cue' => 'Woven textile cross-hatch fibers with natural slub yarn displacement.',
            ];
        }

        return [
            'metallic' => 0.05,
            'roughness' => 0.4,
            'sheen' => 0.2,
            'subsurface' => 0.05,
            'normal_texture_cue' => 'Fine injection-molded soft-touch finish.',
        ];
    }

    protected function determineAnchorType(GeneratedListing $listing): string
    {
        $cat = strtolower((string) ($listing->suggestedCategory ?? ''));
        if (str_contains($cat, 'wall') || str_contains($cat, 'art') || str_contains($cat, 'mirror') || str_contains($cat, 'clock')) {
            return 'vertical_wall';
        }
        if (str_contains($cat, 'rug') || str_contains($cat, 'furniture') || str_contains($cat, 'chair') || str_contains($cat, 'table')) {
            return 'horizontal_floor';
        }

        return 'horizontal_tabletop';
    }
}
