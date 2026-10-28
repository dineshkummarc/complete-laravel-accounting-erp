<?php

return [

    'provider' => env('AI_CATALOG_PROVIDER'),
    'model' => env('AI_CATALOG_MODEL'),
    'vision_model' => env('AI_CATALOG_VISION_MODEL'),
    'embed_model' => env('AI_CATALOG_EMBED_MODEL'),

    'schema' => [
        'model' => env('AI_CATALOG_PRODUCT_MODEL', 'App\\Models\\Product'),
        'table' => env('AI_CATALOG_PRODUCT_TABLE', 'products'),
        'map' => [
            'title' => 'name',
            'meta_title' => 'meta_title',
            'meta_description' => 'meta_description',
            'short_description' => 'excerpt',
            'long_description' => 'description',
            'specs' => 'attributes',
            'tags' => 'tags',
            'category' => 'category_id',
            'subcategory' => 'subcategory_id',
            'locale' => 'locale',
            'faq' => 'faq',
            'care_instructions' => 'care_instructions',
            'size_guide' => 'size_guide',
            'gtin' => 'gtin',
            'mpn' => 'mpn',
            'sku' => 'sku',
            'json_ld' => 'json_ld',
            'alt_text' => 'image_alt',
            'translations' => 'translations',
        ],
        'json_columns' => ['attributes', 'tags', 'faq', 'json_ld', 'translations', 'size_guide'],
        'on_approve' => env('AI_CATALOG_ON_APPROVE', 'update_or_create'),
    ],

    'taxonomy' => [
        'model' => env('AI_CATALOG_CATEGORY_MODEL', 'App\\Models\\Category'),
        'name_column' => 'name',
        'parent_column' => 'parent_id',
        'id_column' => 'id',
        'min_score' => 55,
        'source' => 'model',
        'items' => [],
    ],

    'specs' => [
        'size' => 'string',
        'weight' => 'string',
        'dimensions' => 'string',
        'materials' => 'string',
        'color' => 'string',
        'brand' => 'string',
        'condition' => 'string',
        'capacity' => 'string',
        'voltage' => 'string',
        'warranty' => 'string',
    ],

    'locale' => env('AI_CATALOG_LOCALE', 'en'),
    'locales' => ['en', 'bn', 'es', 'fr', 'de', 'it', 'pt', 'ar', 'hi', 'ja', 'zh', 'ko', 'tr', 'nl', 'id', 'ru', 'th', 'vi', 'pl', 'sv'],
    'tone' => env('AI_CATALOG_TONE', 'professional'),
    'tones' => ['professional', 'persuasive', 'minimal', 'playful', 'luxury', 'technical', 'friendly'],
    'variants' => (int) env('AI_CATALOG_VARIANTS', 2),
    'max_variants' => 3,

    'i18n' => [
        'fanout' => (bool) env('AI_CATALOG_I18N_FANOUT', true),
        'rtl' => ['ar', 'he', 'fa', 'ur'],
        'expansion_warn' => 1.4,
        'glossary' => [
            // 'earbuds' => ['bn' => 'ইয়ারবাড', 'ja' => 'イヤホン'],
        ],
    ],

    'workflow' => [
        'auto_publish' => false,
        'require_review' => (bool) env('AI_CATALOG_REQUIRE_REVIEW', true),
        'low_confidence_threshold' => (float) env('AI_CATALOG_LOW_CONFIDENCE', 0.7),
        'allow_partial_approve' => true,
        'stale_after_days' => (int) env('AI_CATALOG_STALE_DAYS', 90),
    ],

    'quality' => [
        'duplicate_title_threshold' => 85,
        'plagiarism_threshold' => 88,
        'embedding_duplicate' => (bool) env('AI_CATALOG_EMBED_DUPES', false),
        'flag_restricted' => true,
        'restricted_words' => [],
        'region' => env('AI_CATALOG_REGION', 'global'),
        'packs' => [
            'us' => ['miracle cure', 'FDA approved', 'clinically proven', 'guaranteed results'],
            'eu' => ['miracle cure', 'clinically proven', 'pesticide-free' /* organic claims */],
            'uk' => ['miracle cure', 'MHRA approved', 'guaranteed to'],
            'bd' => ['miracle cure', '100% original guarantee'],
            'medical' => ['cures', 'treats disease', 'diagnoses', 'prevents cancer'],
            'global' => ['miracle cure'],
        ],
    ],

    'images' => [
        'alt_text' => (bool) env('AI_CATALOG_IMAGE_ALT', true),
        'ocr' => (bool) env('AI_CATALOG_IMAGE_OCR', true),
        'quality' => true,
        'duplicates' => true,
        'thumbnails' => true,
        'normalize_white' => (bool) env('AI_CATALOG_IMAGE_WHITE', false),
        'webp' => true,
        'avif' => false,
        'thumbnail_widths' => [150, 400, 800],
        'min_width' => 500,
        'min_height' => 500,
        'max_aspect' => 2.2,
        'min_aspect' => 0.45,
        'disk' => env('AI_CATALOG_IMAGE_DISK', 'local'),
        'directory' => 'ai-catalog',
        'lifestyle_prompts' => true,
        'generate_lifestyle' => (bool) env('AI_CATALOG_IMAGE_GEN', false),
        'hash_size' => 8,
    ],

    'voice' => [
        'enabled' => true,
        'endpoint' => env('AI_CATALOG_WHISPER_URL', 'https://api.openai.com/v1/audio/transcriptions'),
        'model' => env('AI_CATALOG_WHISPER_MODEL', 'whisper-1'),
        'api_key' => env('OPENAI_API_KEY'),
        'language' => env('AI_CATALOG_VOICE_LANG'),
    ],

    'cache' => [
        'enabled' => (bool) env('AI_CATALOG_CACHE', true),
        'ttl' => (int) env('AI_CATALOG_CACHE_TTL', 86400),
        'store' => env('AI_CATALOG_CACHE_STORE'),
    ],

    'queue' => env('AI_CATALOG_QUEUE', 'default'),
    'bulk_chunk' => 25,

    'tenancy' => [
        'enabled' => (bool) env('AI_CATALOG_TENANCY', false),
        'column' => 'tenant_id',
        'resolver' => null,
    ],

    'budget' => [
        'enabled' => (bool) env('AI_CATALOG_BUDGET', false),
        'monthly_usd' => (float) env('AI_CATALOG_BUDGET_MONTHLY', 0),
        'on_exceed' => env('AI_CATALOG_BUDGET_ON_EXCEED', 'warn'), // warn|block
    ],

    'api' => [
        'enabled' => (bool) env('AI_CATALOG_API', true),
        'prefix' => env('AI_CATALOG_API_PREFIX', 'api/ai-catalog'),
        'middleware' => array_filter(explode(',', env('AI_CATALOG_API_MIDDLEWARE', 'api'))),
        'rate_limit' => env('AI_CATALOG_API_RATE', '30,1'),
        'per_user_rate' => env('AI_CATALOG_API_USER_RATE', '60,1'),
        'per_tenant_rate' => env('AI_CATALOG_API_TENANT_RATE', '120,1'),
    ],

    'studio' => [
        'enabled' => (bool) env('AI_CATALOG_STUDIO', true),
        'prefix' => env('AI_CATALOG_STUDIO_PREFIX', 'ai-catalog'),
        'middleware' => array_values(array_filter(explode(',', env('AI_CATALOG_STUDIO_MIDDLEWARE', 'web')))),
        'gate' => env('AI_CATALOG_GATE', 'viewAiCatalog'),
    ],

    'webhooks' => [
        // 'https://example.com/hooks/ai-catalog',
    ],
    'webhook_secret' => env('AI_CATALOG_WEBHOOK_SECRET'),

    'feeds' => [
        'currency' => env('AI_CATALOG_CURRENCY', 'USD'),
        'brand_fallback' => env('AI_CATALOG_BRAND', ''),
        'google_condition' => 'new',
    ],

    'prompts' => [
        'system' => null,
        'listing' => null,
        'vision' => null,
        'url' => null,
        'voice' => null,
        'translate' => null,
        'alt_text' => null,
        'ocr' => null,
        'field' => [],
    ],

    'filament' => [
        'enabled' => true,
        'navigation' => true,
        'group' => 'AI Catalog',
        'sort' => 50,
        'widgets' => true,
    ],

    'brand_voices' => [
        // 'premium' => 'Sound like a quiet luxury house brand. Short sentences. No hype.',
    ],

    /*
    |--------------------------------------------------------------------------
    | 2027 Next-Gen Commerce Features
    |--------------------------------------------------------------------------
    |
    | Autonomous agent shopping feeds, Digital Product Passport (DPP),
    | Social Commerce video scripts, Return Reduction, and Spatial Commerce.
    |
    */
    'dpp' => [
        'enabled' => (bool) env('AI_CATALOG_DPP_ENABLED', true),
        'standard' => 'EU-ESPR-DPP-v1',
    ],

    'agentic_feed' => [
        'enabled' => (bool) env('AI_CATALOG_AGENTIC_ENABLED', true),
        'protocol' => 'UCP-2027',
        'citation_domain' => env('APP_URL'),
    ],

    'social_commerce' => [
        'enabled' => (bool) env('AI_CATALOG_SOCIAL_ENABLED', true),
        'platforms' => ['tiktok_shop', 'instagram_reels', 'youtube_shorts'],
        'default_durations' => ['15s', '30s'],
    ],

    'returns_reduction' => [
        'enabled' => (bool) env('AI_CATALOG_RETURNS_ENABLED', true),
        'proactive_faq' => true,
    ],

    'spatial' => [
        'enabled' => (bool) env('AI_CATALOG_SPATIAL_ENABLED', true),
        'default_unit' => 'cm',
        'format' => 'usdz_gltf',
    ],

    'embeddings' => [
        'enabled' => (bool) env('AI_CATALOG_EMBEDDINGS_ENABLED', false),
        'model' => env('AI_CATALOG_EMBED_MODEL', 'text-embedding-3-small'),
    ],

    'audit' => [
        'stale_days' => (int) env('AI_CATALOG_AUDIT_STALE_DAYS', 90),
        'min_confidence' => 0.75,
    ],

];
