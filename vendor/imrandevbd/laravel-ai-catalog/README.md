# Laravel AI Catalog

<p align="center">
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-catalog"><img src="https://img.shields.io/packagist/v/imrandevbd/laravel-ai-catalog.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-catalog"><img src="https://img.shields.io/packagist/dt/imrandevbd/laravel-ai-catalog.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-catalog"><img src="https://img.shields.io/packagist/php-v/imrandevbd/laravel-ai-catalog.svg?style=flat-square" alt="PHP"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-teal.svg?style=flat-square" alt="License"></a>
  <a href="https://imrandev.bd"><img src="https://img.shields.io/badge/by-Imran%20Dev%20BD-14b8a6.svg?style=flat-square" alt="Imran Dev BD"></a>
</p>

**Stop typing product data. Show it, say it, link it, or upload it — let AI build complete, verified, 2027-ready commerce listings.**

AI-powered product catalog data-entry and autonomous intelligence for Laravel. Transform a **photo**, **keyword**, **URL**, **spreadsheet**, **voice note**, or **technical datasheet** into a complete, review-ready listing — titles, SEO, structured specs, taxonomy, tags, FAQs, care copy, JSON-LD, marketplace feeds, and localized variants — across **multiple AI providers** and **20 locales**, protected by a strict **draft → review → approve** governance gate.

Equipped with cutting-edge **2027 Next-Gen Commerce capabilities**: **Digital Product Passport (DPP)** for EU Ecodesign compliance, **Autonomous Shopping Agent feeds (`llms.txt` & UCP)**, **Multi-Channel Social Commerce video storyboards (TikTok Shop / Reels / Shorts)**, **Preemptive Return-Reduction intelligence**, **Spatial Commerce 3D/AR/PBR specifications**, and an **Autonomous Self-Healing Catalog Auditor**.

Built as a high-performance domain layer on [`imrandevbd/laravel-ai-hub`](https://packagist.org/packages/imrandevbd/laravel-ai-hub). This package owns catalog logic; Hub powers providers, failover, structured JSON schemas, embeddings, and token cost tracking.

Laravel **10–13** · PHP **8.2+** · Built by **[Imran Dev BD](https://imrandev.bd/)**

```bash
composer require imrandevbd/laravel-ai-catalog
php artisan vendor:publish --tag=ai-catalog-config
php artisan migrate
```

- **Packagist:** [`imrandevbd/laravel-ai-catalog`](https://packagist.org/packages/imrandevbd/laravel-ai-catalog)
- **GitHub:** [github.com/imranbru99/laravel-ai-catalog](https://github.com/imranbru99/laravel-ai-catalog)
- **Studio Dashboard:** `/ai-catalog` after installation

---

## 5-Minute Setup

### 1. Map to your existing products table
Configure `config/ai-catalog.php` to match your existing schema without writing migration glue:

```php
// config/ai-catalog.php
'schema' => [
    'model' => App\Models\Product::class,
    'map' => [
        'title' => 'name',
        'short_description' => 'excerpt',
        'long_description' => 'description',
        'specs' => 'attributes',
        'category' => 'category_id',
        'json_ld' => 'schema_org',
    ],
],
```

### 2. Generate your first listing

```php
use ImranDevBd\AiCatalog\Facades\AiCatalog;

$draft = AiCatalog::fromKeyword('wireless noise-cancelling earbuds')
    ->locale('en')
    ->locales(['bn', 'ja'])
    ->tone('professional')
    ->variants(2)
    ->withDpp(true)             // 2027 EU Ecodesign Digital Product Passport
    ->withSocial(true)          // TikTok Shop & Reels video scripts
    ->withReturnReduction(true) // Preemptive sizing & tactile handfeel
    ->generate();

// Access generated payload
echo $draft->listing()->title;
echo $draft->listing()->jsonLd['@type'];
$draft->listing()->dpp;            // Repairability score, carbon footprint, recyclability
$draft->listing()->socialScripts;  // 3s hooks, 15s/30s video storyboards
$draft->listing()->lowConfidenceFields(0.75);

// Approve when ready — only this writes to your real products table!
AiCatalog::approve($draft);
```

---

## Master Feature Matrix (Everything Shipped)

| Capability Area | Built-in Features |
|---|---|
| **7 Input Modes** | Keyword seed, unstructured raw text, product photo (multimodal vision), competitor/supplier URL (anti-plagiarism rewrite), bulk CSV/JSON importer, voice recordings (Whisper audio/transcript), and technical datasheets (`.pdf`, `.md`, `.txt`, `.csv`, `.html`). |
| **2027 Next-Gen Commerce** | **Digital Product Passport (DPP)** (repairability score, recycled content %, carbon footprint kg CO2e, GS1 Digital Link), **Autonomous Agent Feeds (`llms.txt` & UCP)**, **Social Commerce Video Studio** (TikTok Shop / Reels / Shorts hooks & storyboards), **Return-Reduction Engine** (fit tendency & sensory expectations), **Spatial Commerce** (3D bounding box, PBR materials, Vision Pro turntable prompts), and **Autonomous Self-Healing Auditor**. |
| **Copy & SEO Intelligence** | Length-optimized SEO titles, meta titles & descriptions, short PDP summaries, long persuasive descriptions, search keywords, 7 tone presets, brand-voice profiles, campaign overlays, and A/B variant copy. |
| **Structured Specs** | Fully configurable spec attributes (dimensions, weight, materials, color, voltage, battery, warranty, hazard flags) plus real-world GTIN, MPN, and SKU grounding (never invented). |
| **Taxonomy Matching** | Fuzzy and exact category/subcategory matching against **your real database** — hallucinated categories are strictly discarded and quarantined with `unmatched_category` flags. |
| **Multi-Language (20 Locales)** | High-throughput fan-out translation pipeline, brand glossary locking, bidirectional RTL support (Arabic, Hebrew, Persian, Urdu), and length-expansion warning flags. |
| **Image Intelligence** | Perceptual average-hash (8×8 GD), Hamming distance duplicate detection, blur/watermark/aspect-ratio flags, automatic thumbnails (JPEG & WebP), accessible alt-text, label OCR, and lifestyle render prompts. |
| **Workflow & Governance** | Strict state machine (`pending` → `generating` → `draft` → `in_review` → `approved` → `published` \| `rejected` \| `failed`), confidence scoring per field, human-in-the-loop review queue, field-level regeneration, and before/after revision logs. |
| **Quality & Compliance** | Title similarity deduplication, regional restricted claim packs (US FDA, EU CE/ESPR, UK MHRA, Bangladesh BSTI, Medical claims), and HMAC-signed webhooks. |
| **Feeds & Integrations** | Schema.org JSON-LD Product, OpenGraph tags, Google Shopping JSON feed, Facebook Catalog CSV, Agentic `llms.txt`, and Universal Commerce Protocol (UCP) endpoints. |
| **Ops & Scale** | Async queue dispatching, batch chunking, 30-day token cost rollup, monthly budget caps (`warn` or `block`), multi-tenant global scoping, and input-hash caching. |
| **Developer Experience (DX)** | Fluent Facade, 8 Artisan CLI commands, REST API with rate limiting, Filament Admin Action & Resource plugin, Livewire 3 component, Blade component, and standalone Studio UI. |

---

## Why This Package? (Competitive Matrix)

Most packages do only one fragment: simple text generation or basic image alt-text. Stores end up stitching together custom code, dealing with hallucinated categories, and risking publishing incorrect product data.

| Feature | Description-Only Tools | Single-Vendor Tools | **Laravel AI Catalog (2027)** |
|---|---|---|---|
| Multi-Provider Failover | One vendor (e.g. OpenAI) | One vendor | **AI Hub (OpenAI, Gemini, Claude, Grok, Ollama, etc.)** |
| Multimodal Vision Input | ❌ | Partial | **Yes (Vision listing + quality + perceptual hash + OCR)** |
| Voice & Datasheets | ❌ | ❌ | **Yes (Whisper API, transcripts, datasheets, markdown)** |
| Digital Product Passport (DPP) | ❌ | ❌ | **Yes (EU Ecodesign 2027 ESPR compliant schema & GS1 Link)** |
| AI Shopping Bot Feeds (`llms.txt`) | ❌ | ❌ | **Yes (`/ai-catalog/llms.txt` + UCP / AP2 agentic JSON)** |
| Social Video Scripts & Hooks | ❌ | ❌ | **Yes (TikTok Shop, Reels, Shorts 15s/30s storyboards)** |
| Returns-Reduction & Fit Engine | ❌ | ❌ | **Yes (Preemptive sizing, tactile drape & mitigation FAQ)** |
| Spatial 3D / AR / PBR Specs | ❌ | ❌ | **Yes (USDZ/glTF bounding box, PBR roughness/metal, Vision Pro cues)** |
| Self-Healing Catalog Auditor | ❌ | ❌ | **Yes (`php artisan ai-catalog:audit` with health score)** |
| Hallucinated Category Guard | Often invents | ❌ | **Strict Match (Never invents categories)** |
| Governance & Audit Trail | Auto-publishes unchecked | ❌ | **Draft → Review → Approve + AI vs Human audit log** |
| Schema Mapping to ANY Model | Opinionated/Rigid | ❌ | **Flexible config map to any `products` table** |
| Ecosystem Integrations | API only | Minimal | **Filament + Livewire 3 + Blade + Studio + REST + CLI** |

---

## 1. Input Modes

### Keyword & Free Text
```php
AiCatalog::fromKeyword('linen throw blanket');
AiCatalog::fromText("Supplier notes: 100% European flax, 130x180cm, stonewashed, natural ivory.");
```

### Multimodal Vision / Product Photo
Extracts product features, materials, colors, brand markings, and physical condition directly from image pixels:
```php
AiCatalog::fromImage(storage_path('app/products/sample.jpg'))->generate();
```

### URL Ingestion (Plagiarism-Safe Rewrite)
Scrapes product pages from suppliers or competitors, strips scripts, and synthesizes an original listing retaining facts while rewriting phrasing:
```php
AiCatalog::fromUrl('https://supplier.example/catalog/sku-10492')->generate();
```

### Voice Notes & Dictation
Uses Whisper to transcribe audio files (MP3, WAV, M4A) or accepts pre-transcribed `.txt` files in offline/test environments:
```php
AiCatalog::fromVoice(storage_path('app/voice/warehouse-voice-note.mp3'))->generate();
```

### Technical Datasheets & Documents
Parses `.pdf`, `.txt`, `.md`, `.csv`, `.json`, and `.html` technical sheets, prioritizing verified engineering specs over marketing fluff:
```php
AiCatalog::fromDocument(storage_path('app/datasheets/industrial-kettle.txt'))->generate();
```

### High-Volume Bulk Importer
Process CSV or JSON files with automated chunking, queued workers, and progress tracking:
```bash
php artisan ai-catalog:bulk storage/imports/catalog_2027.csv
```
```http
POST /api/ai-catalog/bulk
GET  /api/ai-catalog/bulk/{uuid}
```

---

## 2. 2027 Next-Gen Commerce Features (Deep Dive)

### Feature 1: Digital Product Passport (DPP) & EU Ecodesign ESPR Compliance
In 2026–2027, the European Union's Ecodesign for Sustainable Products Regulation (ESPR) mandates verifiable sustainability metrics. Every generated listing automatically includes:
- **Repairability Score** (1.0 to 10.0 scale)
- **Recycled Materials Percentage** (verified post-consumer estimates)
- **Lifecycle Carbon Footprint** (estimated kg CO2e)
- **Durability Rating** (`high`, `medium`, `commercial_grade`)
- **End-of-Life Recyclability Guide**
- **Battery Passport** (chemistry, recharge cycles, WEEE compliance) for electronics
- **GS1 Digital Link URI** (e.g. `https://yourstore.com/dpp/01/{gtin}`)

```php
$listing = $draft->listing();

$listing->dpp['repairability_score'];           // 8.5
$listing->dpp['carbon_footprint_kg'];           // 2.25
$listing->dpp['recycled_materials_percentage']; // 35.0
$listing->dpp['digital_link_uri'];              // https://store.com/dpp/01/00012345678901
```

Feed endpoint:
```http
GET /api/ai-catalog/feeds/dpp
```

---

### Feature 2: Autonomous Agent Shopping Feeds (`llms.txt` & UCP)
In 2027, AI shopping agents (ChatGPT Operator, Google Astra, Claude, Apple Intelligence) act as personal concierges purchasing directly. Stores must supply machine-readable, zero-hallucination catalog feeds:

- **`llms.txt` & `llms-full.txt`**: Standardized Markdown catalog format with compressed context tokens, verified QA, and citation anchors for Generative Engine Optimization (GEO).
- **Universal Commerce Protocol (UCP) / AP2**: Machine-readable JSON structured for autonomous agent query and cart handoff.

```php
// Generate programmatically
$markdown = AiCatalog::llmsTxt(limit: 200, full: true);
$ucpJson = AiCatalog::agenticFeed(limit: 200);
```

Endpoints and CLI:
```http
GET /ai-catalog/llms.txt
GET /api/ai-catalog/feeds/agentic
```
```bash
php artisan ai-catalog:agentic-feed --output=public/llms.txt --full
```

---

### Feature 3: Multi-Channel Social Commerce & Video Script Studio
Over 60% of modern e-commerce discovery occurs on short-form video. The package generates high-converting short-form video production kits:

- **3-Second Visual & Verbal Hooks**:
  - *Curiosity Hook*: "Don't buy until you see this 👀"
  - *Problem/Solution Hook*: "Stop wasting money on cheap alternatives that break in 2 weeks."
  - *Aesthetic/ASMR Hook*: "Satisfying unboxing in 3, 2, 1… listen to this texture."
- **15s, 30s, and 60s Storyboards**: Time-coded scenes with camera direction, B-roll visual cues, spoken voiceover, and on-screen text overlays.
- **TikTok Shop Direct Metadata**: Ready-to-paste pinned comment, trending sound direction, and creator affiliate briefs.

```php
$social = $draft->listing()->socialScripts;

foreach ($social['hooks'] as $hook) {
    echo "Hook ({$hook['type']}): {$hook['verbal']}\n";
    echo "Visual: {$hook['visual']}\n";
}

$scene1 = $social['storyboards']['15s']['scenes'][0];
echo "Time: {$scene1['time']} | Voice: {$scene1['voiceover']}";
```

---

### Feature 4: Preemptive Return-Reduction Engine
Returns are the #1 cost center in modern e-commerce. The package eliminates return triggers *before* the customer orders:
- **Fit Tendency Analysis**: Categorizes fit as `true_to_size`, `structured_snug` (recommends sizing up half a size), or `adaptive_stretch`.
- **Tactile Sensory Copy**: Accurately describes handfeel, drape, thermal feel, and fabric weight to prevent expectation mismatches.
- **Return-Mitigation FAQs**: Injects targeted Q&As addressing common category return reasons.
- **Return Risk Score**: 0 to 100% risk index based on category and material properties.

```php
$returns = $draft->listing()->returnReduction;

echo $returns['return_risk_score'];     // 14
echo $returns['fit_advice'];            // "Structured fit. Consider sizing up half a size."
echo $returns['tactile_expectations'];  // "Supple full-grain texture with satin sheen."
```

---

### Feature 5: Spatial Commerce & 3D/AR/PBR Specifications
Ready for Apple Vision Pro, Meta Quest, and WebXR:
- **3D Bounding Box**: Width × Height × Depth in centimeters and inches.
- **Physically Based Rendering (PBR) Materials**: Roughness, metallicness, transmission, IOR, and normal map texture cues.
- **Turntable Image Generation Prompts**: Optimized prompts for Diffusion models (SDXL, Flux, Midjourney) for 360° product renders (frontal hero, 45° isometric, macro surface grain, spatial room placement).
- **AR Placement Anchors**: Automatically flags `horizontal_tabletop`, `horizontal_floor`, or `vertical_wall`.

```php
$spatial = $draft->listing()->spatialPrompts;

echo $spatial['bounding_box_cm']['width'];               // 18.0 cm
echo $spatial['pbr_material_channels']['metallic'];       // 0.90
echo $spatial['turntable_render_prompts']['hero_frontal'];// "Studio hero shot of..."
```

---

### Feature 6: Autonomous Catalog Health Auditor (`ai-catalog:audit`)
Audit existing catalog listings for quality, compliance, and degradation with autonomous self-healing:
- Checks title/meta character lengths against optimal SEO/GEO thresholds.
- Detects missing commerce specs (dimensions, materials, weight, color).
- Flags stale listings not refreshed within configurable days.
- Evaluates confidence scores and compliance risks.
- Dispatches automated self-healing re-enrichment.

```bash
# Scan catalog health
php artisan ai-catalog:audit --stale=90

# Scan and automatically re-queue healable listings
php artisan ai-catalog:audit --fix
```

Programmatic access:
```php
$audit = AiCatalog::audit(tenantId: null, staleDays: 90);
echo "Catalog Health: {$audit['health_score']}%";
```

---

### Feature 7: Semantic Vector Embeddings for Hybrid Search
Generate vector embeddings during listing generation for vector databases (pgvector, Meilisearch, Qdrant, Pinecone, Laravel Scout):

```php
$draft = AiCatalog::fromKeyword('ergonomic chair')
    ->withEmbeddings(true)
    ->generate();

$vector = $draft->listing()->embedding; // Array of floats (1536-dim or custom)
```

---

## 3. What Gets Generated (Listing Payload)

Every generation persists a `GeneratedListing` data object:

| Field | Description |
|---|---|
| `title`, `meta_title`, `meta_description` | Length-optimized SEO titles and descriptions. |
| `short_description`, `long_description` | Concise summary and full persuasive marketing copy. |
| `specs` | Key-value pairs configured in `config('ai-catalog.specs')`. |
| `tags`, `search_keywords` | Faceted navigation tags and on-site search keywords. |
| `suggested_category`, `category_id` | Validated against your database (hallucinations stripped). |
| `faq[]` | 3–5 high-converting question/answer pairs. |
| `care_instructions`, `size_guide`, `shipping_notes` | Operational guidelines and care advice. |
| `gtin`, `mpn`, `sku` | Real-world barcodes and identifiers (never invented). |
| `variants[]` | A/B testing headlines, short descriptions, and campaign overlays. |
| `confidence{}` | 0.0 to 1.0 confidence score per field and overall. |
| `flags[]` | `unmatched_category`, `duplicate_title`, `restricted:*`, image warnings. |
| `json_ld`, `open_graph` | Schema.org Product structured data and social meta tags. |
| `translations{locale}` | Complete translated listings for localized variants. |
| `alt_text`, `ocr_text`, `image_report` | Image accessibility, extracted text, and quality report. |
| `dpp` | Digital Product Passport (repairability, carbon, circularity). |
| `social_scripts` | TikTok Shop, Reels, and Shorts hooks and storyboards. |
| `return_reduction` | Preemptive fit advice, tactile feel, and return risk. |
| `spatial_prompts` | 3D bounding box, PBR materials, and Vision Pro prompts. |
| `embedding` | Semantic vector array for hybrid search. |

---

## 4. Multi-Language & Global Localization

One single input automatically fans out across up to **20 supported locales**:

```php
AiCatalog::fromKeyword('ceramic pour-over kettle')
    ->locale('en')
    ->locales(['bn', 'ja', 'ar', 'de', 'es', 'fr'])
    ->generate();
```

- **Glossary Locking**: Force exact brand and technical terms in `config('ai-catalog.i18n.glossary')`.
- **RTL Support**: Locales `ar`, `he`, `fa`, `ur` receive automatic `rtl: true` flags.
- **Expansion Ratio Warnings**: Flags translations that exceed `1.4×` character length of original copy.
- **Supported Locales**: `en`, `bn`, `es`, `fr`, `de`, `it`, `pt`, `ar`, `hi`, `ja`, `zh`, `ko`, `tr`, `nl`, `id`, `ru`, `th`, `vi`, `pl`, `sv`.

---

## 5. Image Intelligence & Quality Suite

When passing an image input (`fromImage` or `--image`):

- **Average Perceptual Hash (aHash)**: 8×8 grayscale matrix computed via GD. Near-duplicates are detected via Hamming distance.
- **Tenant Duplicate Index**: Tracks existing image hashes in `ai_catalog_image_hashes`.
- **Quality Inspection**: Checks min width/height (500px), aspect ratio constraints, blur variance, and watermark text sniffs.
- **WebP & Thumbnail Generation**: Generates configurable thumbnail widths (150px, 400px, 800px) with automatic WebP conversion.
- **OCR Text Sniffing**: Extracts physical label text, ingredients, and regulatory marks.
- **Accessible Alt-Text**: Screen-reader and SEO-optimized descriptive alt text.

Inspect an image via CLI:
```bash
php artisan ai-catalog:inspect-image storage/app/products/hero.jpg
```

---

## 6. Taxonomy: Hallucination-Free Category Matching

AI models frequently invent categories that break store navigation. This package matches **strictly against your database**:

```php
// config/ai-catalog.php
'taxonomy' => [
    'model' => App\Models\Category::class,
    'name_column' => 'name',
    'parent_column' => 'parent_id',
    'min_score' => 55, // Minimum similarity threshold (0-100)
],
```

If the AI suggests "Vintage Footwear" but your store only has "Shoes > Sneakers", the matcher will map it correctly or quarantine it with an `unmatched_category` flag. **The package will never invent or insert unapproved category records.**

---

## 7. Universal Schema Mapping

Approve is the only operation that writes to your live product table. It adapts to any database column names:

```php
'schema' => [
    'model' => App\Models\Product::class,
    'map' => [
        'title' => 'name',
        'short_description' => 'summary',
        'long_description' => 'body',
        'specs' => 'specifications',
        'faq' => 'faq_items',
        'json_ld' => 'structured_data',
        'dpp' => 'sustainability_passport',
        'alt_text' => 'thumbnail_alt',
    ],
    'json_columns' => ['specifications', 'faq_items', 'structured_data', 'sustainability_passport'],
    'on_approve' => 'update_or_create', // update_or_create, update_only, create_only
],
```

Set any mapping key to `null` to skip writing that field.

---

## 8. Workflow, Governance & Revision Audit

Every generation follows a governed lifecycle:

```
pending → generating → draft → in_review → approved → published
                              ↘ rejected
                              ↘ failed
```

- **Low-Confidence Gate**: Listings with confidence below `config('ai-catalog.workflow.low_confidence_threshold')` (default 0.70) cannot be auto-approved without human inspection.
- **Field-Level Regeneration**: Re-run AI on individual fields without regenerating the entire listing:
  ```php
  AiCatalog::regenerateField($draft, 'title');
  AiCatalog::regenerateField($draft, 'faq');
  AiCatalog::regenerateField($draft, 'alt_text');
  ```
- **Revision Audit Log**: Every edit, generation, and approval writes to `ai_catalog_revisions` storing actor (`ai` vs `human`), before/after diffs, and timestamps.
- **Stale Listing Refresh**: Automatically re-queue approved listings older than N days:
  ```bash
  php artisan ai-catalog:refresh-stale --days=90
  ```
- **Webhooks & HMAC Signatures**: Dispatches events to remote endpoints with `X-Ai-Catalog-Signature` HMAC-SHA256 headers.

---

## 9. Data Quality & Regional Compliance Packs

Prevent legal liability by flagging prohibited marketing and medical claims before listings go live:

- **US Pack**: Flags "FDA approved", "clinically proven", "miracle cure", "guaranteed results".
- **EU Pack**: Flags unsupported organic claims, greenwashing terms, and uncertified medical assertions.
- **UK Pack**: Flags "MHRA approved", misleading guarantees.
- **BD Pack**: Flags counterfeit guarantee terms and deceptive claims.
- **Medical Pack**: Flags "cures", "treats disease", "diagnoses", "prevents cancer".
- **Custom Store Words**: Add your own disallowed words in `config('ai-catalog.quality.restricted_words')`.

```php
AiCatalog::fromKeyword('herbal wellness tea')->region('eu')->generate();
```

---

## 10. Commerce Feeds & Structured Data

Generate verified marketplace and search engine feeds:

```bash
# Export Google Shopping feed (JSON)
php artisan ai-catalog:export-feed --format=google

# Export Facebook Catalog feed (CSV)
php artisan ai-catalog:export-feed --format=facebook --output=storage/app/facebook.csv
```

Programmatic access:
```php
$googleItems = AiCatalog::googleFeed(limit: 200);
$agenticItems = AiCatalog::agenticFeed(limit: 200);
$dppItems = AiCatalog::dppFeed(limit: 200);
```

REST Endpoints:
```http
GET /api/ai-catalog/feeds/google
GET /api/ai-catalog/feeds/agentic
GET /api/ai-catalog/feeds/dpp
```

---

## 11. Ops, Costs, Caching, Tenancy & Queues

| Control | Implementation |
|---|---|
| **Token Cost Tracking** | Records `prompt_tokens`, `completion_tokens`, and exact `cost_usd` per listing. |
| **30-Day Cost Rollup** | `AiCatalog::costs()`, Studio dashboard widgets, Filament stats card. |
| **Monthly Budget Cap** | `config('ai-catalog.budget')` with `warn` or `block` behavior on overages. |
| **Smart Caching** | SHA-256 input payload hashing prevents re-billing for identical prompts. |
| **Async Queuing** | `$pending->generateAsync()` queues job on `config('ai-catalog.queue')`. |
| **Multi-Tenancy** | Automatic tenant scoping with custom resolver closure in `ai-catalog.tenancy`. |

---

## 12. Full Developer Experience (DX)

### Artisan CLI Commands

```bash
# Generate single listing
php artisan ai-catalog:generate --keyword="ergonomic desk" --locale=en --locales=bn,ja --tone=minimal

# Generate from photo, audio, or document
php artisan ai-catalog:generate 42 --image="storage/products/42.jpg"
php artisan ai-catalog:generate --audio="storage/notes.mp3"
php artisan ai-catalog:generate --document="storage/datasheet.pdf"

# Bulk import from CSV/JSON
php artisan ai-catalog:bulk storage/imports/catalog.csv

# 2027 Autonomous Catalog Health Audit & Self-Healing
php artisan ai-catalog:audit --stale=90
php artisan ai-catalog:audit --fix

# 2027 Agentic llms.txt feed export
php artisan ai-catalog:agentic-feed --output=public/llms.txt --full

# Ops maintenance
php artisan ai-catalog:refresh-stale --days=90
php artisan ai-catalog:retry-failed
php artisan ai-catalog:inspect-image storage/app/sample.jpg
php artisan ai-catalog:export-feed --format=google
```

### Studio UI Dashboard
Visit **`/ai-catalog`** in your browser to manage the draft review queue, inspect 30-day AI spending, track bulk imports, and approve listings with one click.

### Filament Admin Plugin
Embed AI generation actions directly onto your Filament Product resources:

```php
use ImranDevBd\AiCatalog\Filament\Actions\GenerateWithAiAction;
use ImranDevBd\AiCatalog\Filament\AiCatalogPlugin;

// On Product Resource Table or Page
GenerateWithAiAction::make();

// In Filament Panel Provider
$panel->plugin(AiCatalogPlugin::make());
```

### Livewire 3 & Blade Components
```blade
{{-- Livewire component --}}
<livewire:ai-catalog.generate-product :product-id="$product->id" />

{{-- Blade button --}}
<x-ai-catalog-generate-button :product="$product" keyword="linen duvet" />
```

### REST API Reference

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/ai-catalog/generate` | Generate listing from JSON payload |
| `GET` | `/api/ai-catalog/generations/{uuid}` | Fetch generation status and payload |
| `POST` | `/api/ai-catalog/generations/{uuid}/review` | Submit draft to review queue |
| `POST` | `/api/ai-catalog/generations/{uuid}/approve` | Approve draft and write to database |
| `POST` | `/api/ai-catalog/generations/{uuid}/reject` | Reject draft with reason |
| `POST` | `/api/ai-catalog/generations/{uuid}/regenerate/{field}` | Regenerate specific field |
| `POST` | `/api/ai-catalog/bulk` | Dispatch bulk import CSV/JSON |
| `GET` | `/api/ai-catalog/bulk/{uuid}` | Check bulk import progress |
| `GET` | `/api/ai-catalog/costs` | 30-day token spend and request count |
| `GET` | `/api/ai-catalog/audit` | Catalog health audit scorecard |
| `GET` | `/api/ai-catalog/feeds/google` | Google Shopping product feed |
| `GET` | `/api/ai-catalog/feeds/agentic` | Universal Commerce Protocol (UCP) feed |
| `GET` | `/api/ai-catalog/feeds/dpp` | Digital Product Passport (DPP) feed |
| `GET` | `/ai-catalog/llms.txt` | Standard `llms.txt` for AI shopping bots |

---

## 13. Testing & Mocking

Test your catalog integrations without making real AI API calls:

```php
use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AiCatalog\Facades\AiCatalog;

// Mock AI Hub response
AIHub::fake(json_encode([
    'title' => 'Aurora Wireless Noise-Cancelling Earbuds',
    'meta_title' => 'Aurora Wireless Earbuds',
    'meta_description' => 'ANC earbuds with 28-hour battery life.',
    'short_description' => 'Lightweight noise-cancelling earbuds.',
    'long_description' => 'Experience studio-calm sound on your daily commute...',
    'specs' => ['brand' => 'Aurora', 'color' => 'Midnight Black'],
    'tags' => ['earbuds', 'wireless'],
    'suggested_category' => 'Electronics',
    'suggested_subcategory' => 'Headphones',
    'confidence' => ['title' => 0.95],
    'variants' => [],
]));

$draft = AiCatalog::fromKeyword('earbuds')->generate();

$this->assertSame('Aurora Wireless Noise-Cancelling Earbuds', $draft->listing()->title);
```

Run test suite:
```bash
vendor/bin/phpunit
```

---

## 🤝 Let's Build Something Exceptional

I'm actively open to:  
**Remote Senior Full-Stack Roles · Freelance Contracts · Technical Partnerships · Long-Term Collaborations**  
in **Laravel · WordPress · React/Next.js · AI-powered Platforms · Security Audits · SaaS Architecture**

📍 **Timezone:** UTC+6 (Dhaka/Rangpur) — flexible overlap for US, EU & Asia  
⚡ **Available:** Immediately · Production-first · Fast delivery · Transparent communication

| Platform | Link |
|---|---|
| 🌐 Portfolio | [imrandev.bd](https://imrandev.bd/) |
| 💼 LinkedIn | [linkedin.com/in/imranbru99](https://www.linkedin.com/in/imranbru99/) |
| 🐙 GitHub | [github.com/imranbru99](https://github.com/imranbru99) |
| 🐦 X / Twitter | [@imrandev_bd](https://x.com/imrandev_bd) |
| 📺 YouTube | [@ImranDevBD](https://www.youtube.com/@ImranDevBD) |
| 📸 Instagram | [@imranbru99](https://www.instagram.com/imranbru99/) |
| 📘 Facebook | [ExpertImranDev](https://www.facebook.com/ExpertImranDev/) |
| 🎵 TikTok | [@imrandev_bd](https://www.tiktok.com/@imrandev_bd) |
| 🧵 Threads | [@imranbru99](https://www.threads.com/@imranbru99) |
| 📌 Pinterest | [@imrandev_bd](https://www.pinterest.com/imrandev_bd/) |
| 💬 WhatsApp | [+880 1576-918420](http://wa.me/+8801576918420) |
| 📧 Email | [me@imrandev.bd](mailto:me@imrandev.bd) |
| 🔗 All Links | [linktr.ee/ExpertImranDev](https://linktr.ee/ExpertImranDev) |

> *"Security isn't an add-on — it's the foundation. Scale, speed, and trust drive every line of code I write."*  
> — **Imran Ahmed**

---

## License

The MIT License (MIT). Created and maintained with ❤️ by **[Imran Dev BD](https://imrandev.bd)**.

