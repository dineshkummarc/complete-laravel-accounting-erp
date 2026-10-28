<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Data\GenerationInput;

class SocialCommerceGenerator
{
    /**
     * Generate social commerce hooks, video storyboards, and TikTok Shop metadata.
     *
     * @return array<string, mixed>
     */
    public function generate(GeneratedListing $listing, ?GenerationInput $input = null): array
    {
        $title = $listing->title;
        $category = $listing->suggestedCategory ?? 'General';
        $brand = $listing->specs['brand'] ?? 'our brand';

        $hooks = [
            [
                'type' => 'curiosity',
                'verbal' => "If you still haven't upgraded your {$category} setup, wait until you see this.",
                'visual' => 'Close-up dynamic reveal, fast macro snap zoom on the main product feature.',
                'on_screen_text' => "Don't buy until you see this 👀",
            ],
            [
                'type' => 'problem_solution',
                'verbal' => "Stop wasting money on cheap alternatives that break in 2 weeks. The {$title} fixes that.",
                'visual' => 'Split-screen or quick cut: frustration with old product vs effortless delight with this product.',
                'on_screen_text' => 'The upgrade you actually needed ⚡',
            ],
            [
                'type' => 'aesthetic_unboxing',
                'verbal' => 'Satisfying unboxing in 3, 2, 1… listen to this texture.',
                'visual' => 'Clean aesthetic tabletop lighting, hands peeling packaging, crisp tactile audio.',
                'on_screen_text' => 'Aesthetic unboxing 📦✨',
            ],
        ];

        $storyboards = [
            '15s' => [
                'target_platform' => ['tiktok_shop', 'instagram_reels', 'youtube_shorts'],
                'target_duration' => '15 seconds',
                'scenes' => [
                    [
                        'time' => '0:00 - 0:03',
                        'visual' => 'Fast hook: hands hold product right in front of camera lens with dynamic light reflection.',
                        'voiceover' => "Everyone's been asking about this: the {$title}.",
                        'text_overlay' => "Viral {$category} find 🔥",
                    ],
                    [
                        'time' => '0:03 - 0:10',
                        'visual' => 'Rapid cut B-roll demonstrating top 2 features in practical daily use.',
                        'voiceover' => $listing->shortDescription ?: "Incredible quality, premium feel, and built to last.",
                        'text_overlay' => 'Premium materials + clean design',
                    ],
                    [
                        'time' => '0:10 - 0:15',
                        'visual' => 'Camera pushes in to CTA with yellow cart icon overlay.',
                        'voiceover' => 'Link is right on the video below. Grab yours while stock lasts!',
                        'text_overlay' => 'Tap yellow basket below 🛒👇',
                    ],
                ],
            ],
            '30s' => [
                'target_platform' => ['tiktok_shop', 'youtube_shorts'],
                'target_duration' => '30 seconds',
                'scenes' => [
                    [
                        'time' => '0:00 - 0:04',
                        'visual' => 'Punchy hook: Show the problem then dramatically present the product.',
                        'voiceover' => "I was skeptical at first, but {$title} completely changed my daily routine.",
                        'text_overlay' => 'Worth the hype? 🤔',
                    ],
                    [
                        'time' => '0:04 - 0:15',
                        'visual' => 'Detailed walkthrough of materials, fit, or ease of use with macro closeups.',
                        'voiceover' => "Notice the build quality. {$listing->longDescription}",
                        'text_overlay' => 'Real customer perspective ⭐⭐⭐⭐⭐',
                    ],
                    [
                        'time' => '0:15 - 0:24',
                        'visual' => 'Social proof demonstration / stress test / aesthetic showcase in natural lighting.',
                        'voiceover' => 'Thousands of 5-star reviews can’t be wrong. It feels 10x more expensive than it is.',
                        'text_overlay' => 'Fast shipping + hassle-free returns',
                    ],
                    [
                        'time' => '0:24 - 0:30',
                        'visual' => 'End card with point to bottom-left shop button and discount banner.',
                        'voiceover' => 'They keep selling out, so check if your size/color is still in stock below!',
                        'text_overlay' => 'Check live flash deal ⚡',
                    ],
                ],
            ],
        ];

        return [
            'hooks' => $hooks,
            'storyboards' => $storyboards,
            'tiktok_shop' => [
                'pinned_comment' => "Tap the bottom-left cart before this flash bundle sells out! 🛒✨ Reply with any sizing or spec questions and we'll reply right away.",
                'sound_direction' => 'Trending chill upbeat hip-hop beat / satisfying ASMR foley sound.',
                'creator_brief' => [
                    'Show the product in authentic daily context within the first 2 seconds.',
                    'Highlight: '.($listing->specs['materials'] ?? 'premium build quality').'.',
                    'Always mention the satisfaction guarantee and fast dispatch.',
                ],
            ],
        ];
    }
}
