# Changelog

All notable changes to **Laravel AI Hub** are documented here.

Packagist: [`imrandevbd/laravel-ai-hub`](https://packagist.org/packages/imrandevbd/laravel-ai-hub) · GitHub: [imranbru99/laravel-ai-hub](https://github.com/imranbru99/laravel-ai-hub)

## [1.7.1] — 2026-10-26

**Studio v1.7.1.** Visual telemetry and documentation release:
- **Visual Studio Showcase**: Added visual UI mockups to `README.md` for Provider Credentials Dashboard, Interactive Playground with real-time SSE streaming & hybrid reasoning controls, Conversational Multi-turn dialogue simulation, and 30-day Costing & Usage Analytics with realistic telemetry demo data.
- **Packagist Release Alignment**: Packagist immutability sync.

---

## [1.7.0] — 2026-10-26

**Studio v1.7.0.** Dynamic model routing & purpose-based multi-provider switching, frontier models (Google Gemini 4 Argon, OpenAI GPT-6 Luna / GPT-5.6 Luna / GPT-6 Sol & Astra), expanded 2026 model catalog (OpenAI GPT-4.5/o1-pro, Gemini 2.5 Flash Lite, Claude Sonnet 4 / Haiku 4, DeepSeek V3/R1, Mistral Saba), cross-provider failover model normalization, scoped priority chains, and Studio v1.7.0.

### Highlights

- **Frontier Spotlight — Google Gemini 4 Argon & OpenAI GPT Luna**:
  - **Google Gemini 4 Argon** (`gemini-4-argon`, `gemini-argon`): Google DeepMind's flagship frontier reasoning model featuring an industry-leading **1-million-token context window** and 1M token output capacity, tailored for autonomous multi-step software engineering, deep mathematics, and cybersecurity vulnerability remediation. Added full hybrid thinking budget support (`->thinking(4096)`). Pricing: $2.00 input / $10.00 output per 1M tokens.
  - **OpenAI GPT-6 Luna & GPT-5.6 Luna** (`gpt-6-luna`, `gpt-5.6-luna`): OpenAI's ultra-fast, high-efficiency models designed for high-throughput, low-latency, and cost-sensitive tasks ($0.10 input / $0.50 output per 1M tokens for `gpt-6-luna`), alongside mid-tier `gpt-6-sol` and frontier `gpt-6-astra`.
- **Dynamic Model Selection & Auto-Provider Detection**:
  - `AIHub::model('gemini-4-argon')` or `AIHub::model('gemini-3.8-flash')`: automatically infers and routes to Google Gemini driver even if OpenAI is configured as the global default.
  - `AIHub::model('gpt-6-luna')` or `AIHub::model('gpt-5.6-luna')`: seamlessly routes to OpenAI.
  - `AIHub::model('claude-3-7-sonnet-latest')`: seamlessly routes to Anthropic Claude.
  - `AIHub::model('deepseek-reasoner')`: routes to DeepSeek.
  - Custom models can be used freely across providers without rigid driver coupling.
- **Unified `using()` Provider & Model Helper**:
  - Added `AIHub::using('gemini', 'gemini-4-argon')` directly on the `AIHub` facade and manager for fluent one-line provider + model targeting.
- **Scoped Priority & Fallback Chains**:
  - Added `AIHub::priority(['gemini', 'openai'])`: overrides the global failover chain for a specific request. Perfect when your application default is OpenAI, but high-volume extraction or vision tasks prioritize Gemini first.
  - Added `AIHub::fallback(['claude', 'gemini'])`: alias for intuitive fallback chain definition.
- **Cross-Provider Failover Model Normalization**:
  - Solved model ID collisions in heterogeneous failovers: when a request with an explicit model (e.g. `gemini-4-argon`) fails over to a secondary provider (e.g. `openai`), AI Hub automatically normalizes the model to the secondary provider's configured default model rather than crashing on an invalid model ID.
- **Updated Live Provider Models Catalog & Pricing**:
  - **OpenAI**: Added `gpt-6-luna`, `gpt-6-sol`, `gpt-6-astra`, `gpt-6`, `gpt-6-mini`, `gpt-4.5-preview`, `gpt-4.5`, `o1-pro`, and `gpt-5-chat-latest` with token pricing.
  - **Google Gemini**: Added `gemini-4-argon`, `gemini-argon`, `gemini-2.5-flash-lite`, verified full support for Gemini 3.8 / 3.7 / 3.0 / 2.5 / 2.0 series.
  - **Anthropic Claude**: Added `claude-opus-5.5`, `claude-sonnet-5.5`, `claude-haiku-5.5`, `claude-fable-5.1`, `claude-opus-4.5`, `claude-sonnet-4-latest`, `claude-haiku-4-latest`.
  - **xAI Grok**: Added `grok-4.7` (flagship reasoning, 500k context), `grok-4.20`, `grok-build-0.1`, and `grok-vision-beta`.
  - **DeepSeek**: Added `deepseek-v4.1-flash` (1M context, multimodal, lightweight KV-cache), `deepseek-v4-pro`, `deepseek-v4`, and explicit `deepseek-v3` / `deepseek-r1` aliases and pricing.
  - **Mistral**: Added `mistral-large-4` (1M context frontier), `mistral-medium-3.5`, `mistral-small-4`, `mistral-saba-latest` ($0.20 / $0.60 per 1M tokens), `devstral-latest`, `ministral-14b-latest`, and `codestral-2501`.
  - **Azure OpenAI**: Added `gpt-6-luna`, `gpt-6`, `gpt-4.5-preview`, and `o1-pro`.
  - **Together & Fireworks**: Added `Qwen/Qwen2.5-Coder-32B-Instruct` and `deepseek-v3`.
  - **OpenRouter**: Added live routes for `google/gemini-4-argon`, `openai/gpt-6-luna`, `anthropic/claude-sonnet-5.5`, and `deepseek/deepseek-v4.1-flash`.
- **Conversational Chat Sessions (`Conversation`)**:
  - Added `->using('provider', 'model')`, `->priority(['...'])`, and automatic provider detection from `$chat->model('...')` or `AIHub::chat(model: '...')`.
- **Studio Dashboard**:
  - Updated badge to `Studio v1.7.0`.
- **Package Keywords & Tags**:
  - Expanded Composer keywords and metadata tags (`gemini-argon`, `gpt-luna`, `gpt-6-luna`, `gemini-4-argon`, `deepseek-v4`, `model-routing`, `laravel-ai`, `ai-studio`, `chat-sessions`, `streaming`).

---

## [1.6.0] — 2026-09-08

**Studio v1.6.0.** Testing assertion engine (`AIHub::fake()`), multi-turn conversational chat sessions (`AIHub::chat()`), Server-Sent Events (SSE) streaming (`streamResponse()` & `streamRaw()`), structured JSON schemas (`asJsonSchema()`, `structured()`), and rich request inspection getters.

### Highlights

- **`AIHub::fake()` Testing Assertion Engine**:
  - Full mocking support with `AIHub::fake()` matching string responses, arrays, callbacks, or sequential response queues.
  - Laravel-style assertions: `AIHub::assertSent()`, `AIHub::assertNotSent()`, `AIHub::assertSentCount()`, and `AIHub::assertNothingSent()`.
  - Convenient factory helper `AIHub::response('content', 'provider', 'model')` to quickly build mock `AiResponse` objects.
- **Multi-Turn Conversational Chat Sessions (`Conversation`)**:
  - `AIHub::chat(?provider, ?model)` fluent conversational session management.
  - Context retention across multi-turn exchanges with automatic token and cost accumulation across all turns.
  - Methods: `send()`, `stream()`, `streamResponse()`, `system()`, `temperature()`, `maxTokens()`, `clear()`, `export()`, `load()`, `getMessages()`, `totalTokens()`, `totalCost()`.
- **Server-Sent Events (SSE) Browser Streaming**:
  - `streamResponse(?callable $onChunk = null, array $headers = [])`: returns a Symfony `StreamedResponse` formatted with standard `text/event-stream` SSE events (`data: {"chunk": "...", "done": false}` and `data: [DONE]`).
  - `streamRaw(callable $onChunk)`: direct closure-driven consumption of streamed chunks.
  - Safe buffer flushing that handles production web servers (Nginx/Apache fastcgi) and avoids terminal buffer pollution during automated tests.
- **Structured Outputs & JSON Schemas**:
  - `->asJsonSchema(array $schema, ?string $name = 'response', ?string $description = null)` and `AIHub::structured(array $schema)`: enforces strict schema outputs across OpenAI, Azure, Gemini, Claude, and OpenAI-compatible providers.
  - Native provider payload mapping: Gemini `responseSchema` & `responseMimeType: application/json`, Claude system schema prompt injection, OpenAI `response_format.json_schema`.
- **Request Inspection Getters**:
  - Added fluent getters on `PendingRequest`: `getProvider()`, `getModel()`, `getPrompt()`, `getMessages()`, `getTemperature()`, `getMaxTokens()`, `getTools()`, `getToolChoice()`, `getImages()`, `getMeta()`, `getResponseSchema()`, `isRecoverJson()`, `isForceJsonObject()`, `hasJobTrace()`.
- **Studio Dashboard**:
  - Updated badge to `Studio v1.6.0`.

---

## [1.5.0] — 2026-09-07

**Studio v1.5.0.** Google Gemini 3.8 release, hybrid thinking engine, Claude extended thinking, latest 2026 provider models, and Studio UI controls.

### Highlights

- **Google Gemini 3.8 Series**: Added `gemini-3.8-flash` (new default for Gemini), `gemini-3.8-pro`, and `gemini-3.8-flash-lite` with full token pricing and thinking support.
- **Hybrid Thinking & Reasoning Engine**:
  - `->thinking(?int $budget = 2048)`: fluent control of reasoning budgets for Gemini 3.8/3.7 (`thinkingConfig`) and Claude 3.7 Sonnet (`extended thinking`).
  - `->withoutThinking()`: explicit zero-thinking budget for lowest possible response latency.
  - `->reasoningEffort('low'|'medium'|'high')`: reasoning effort for OpenAI o1, o3, o4-mini, and GPT-5 models.
- **Anthropic Claude Updates**: Added Claude Opus 4 (`claude-opus-4-latest`), automatic temperature/max_tokens management for Claude 3.7 extended thinking.
- **Latest Provider Releases**: Added Perplexity `sonar-deep-research`, Grok 4.6, and OpenRouter Gemini 3.8 models.
- **Studio Playground UI**:
  - Added Hybrid Thinking toggle and token budget selector.
  - Added Reasoning Effort selector for OpenAI o-series/GPT-5 models.
  - Added one-click **Copy Reply** button with instant feedback toast.
- **Intelligence API**: Added `AIHub::models(?string $provider = null)` and `AIHub::capabilities(string $model)`.

---

## [1.4.0] — 2026-08-27

**Studio v1.4.0.** Playground and the fluent API now match each model’s real parameter rules. Filament admin opens Studio in a new tab. Everything from 1.3 still ships: 13 providers, tools, vision, budgets, cache, failover, analytics.

### Highlights

- GPT-5.6 Luna / Terra / Sol, GPT-5, o3, o1, and DeepSeek-R1 no longer 400 on `temperature: 0.7`
- OpenAI reasoning models receive `max_completion_tokens` instead of `max_tokens`
- Filament sidebar **AI Hub** (and dashboard widget stats) open `/ai-hub` in a new browser tab
- Unknown models that still reject sampling fields are retried once with those fields dropped

### Added

- **`ModelCapabilities`** — Detects reasoning vs chat models (including `openai/gpt-5` OpenRouter IDs). Omits `temperature`, `top_p`, `presence_penalty`, and `frequency_penalty` where the API rejects them. Remaps `max_tokens` → `max_completion_tokens` for OpenAI / Azure / OpenRouter GPT-5 & o-series. `gpt-5-chat*` and GPT-4o still get normal sampling.
- **Playground (smart controls)** — Temperature input disables on reasoning models with a short explanation. Token field is labeled **Max completion** when remapped. Requests never send a rejected temperature. Default cap is 2048.
- **Filament admin link** — Auto-registers an **AI Hub** navigation item on Filament v3/v4 panels (`target=_blank`). Optional `AiHubPlugin::make()`. Widget stats link to Studio the same way. Config: `AI_HUB_FILAMENT_NEW_TAB`, `AI_HUB_FILAMENT_LABEL`, `AI_HUB_FILAMENT_GROUP`, `AI_HUB_FILAMENT_ICON`, `AI_HUB_FILAMENT_SORT`.
- **Claude clamp** — Temperature is capped to `0–1` (Anthropic’s range).
- **Connection tester** — Does not send a 32-token cap on reasoning models (hidden reasoning would consume the whole budget).
- **Tests** — `ModelCapabilitiesTest` covers GPT-5.6, o-series, `gpt-5-chat-latest`, DeepSeek reasoner, false positives (`o10`, `gpt-50`), and OpenAI 400 message detection.

### Changed

- Studio badge is **v1.4.0**.
- `PendingRequest` strips unsupported sampling before the HTTP call, so `->temperature(0.7)` is safe on GPT-5.

### Fixed

- Playground HTTP 400: `Unsupported value: 'temperature' does not support 0.7 with this model. Only the default (1) value is supported.`
- Playground / fluent API HTTP 400: `Unsupported parameter: 'max_tokens' is not supported with this model. Use 'max_completion_tokens' instead.`

---

## Feature map (current)

| Area | What you get |
|---|---|
| **Providers** | OpenAI · Gemini · Claude · Grok · DeepSeek · Mistral · Groq · Ollama · OpenRouter · Azure OpenAI · Together · Fireworks · Perplexity |
| **Studio** | `/ai-hub` — Playground (send + SSE stream), keys, priority chain, analytics, light/dark, prompt templates |
| **Fluent API** | `AIHub::openai()->prompt()->send()`, tools, vision, `cache()`, `promptTemplate()`, failover |
| **Reasoning models** | Auto-omit temperature; remap max tokens; one 400 retry |
| **Cost & budgets** | USD per 1M tokens, monthly / provider / job caps (`block` or `warn`) |
| **Reliability** | Retries + jitter on 429/5xx, JSON auto-repair, zero-worker `after_response` logs |
| **Admin** | Filament widget + **AI Hub** nav (new tab), `AIHub::auth()`, roles, emails, Gate |
| **CLI** | `php artisan ai-hub:configure` |

---

## [1.3.0] — 2026-08-23

### Added
- **Studio Playground**: Live prompt runner on `/ai-hub` with Send and SSE Stream, cost/latency/token readout, optional vision image URL, and draft persistence in `localStorage`.
- **Tool calling & vision**: `tools()`, `toolChoice()`, `image()`, and `images()` on the fluent API. `AiResponse::$toolCalls` for follow-up turns. Mapped for OpenAI-compatible, Azure, Claude, and Gemini.
- **Spend budgets**: Monthly USD cap, optional per-provider and per-job caps, `block` or `warn` when exceeded. Editable on the Analytics tab.
- **Prompt templates**: Named templates with `{var}` interpolation via `AIHub::promptTemplate()`, savable from Playground.
- **Response cache**: `->cache($ttl)` skips the HTTP call on hit and logs `cache_hit` at $0.
- **Providers**: Azure OpenAI, Together, Fireworks, and Perplexity (OpenAI-compatible except Azure's deployment URL).

### Changed
- Studio version badge is now **v1.3.0**. Catalog expanded to 13 providers.

## [1.2.2] — 2026-08-23

### Fixed
- **Studio light mode**: Contrast on unsaved badges, accent labels, checkboxes, native `<select>` menus, and footer links so light theme is fully usable.
- **Theme flash**: Saved light/dark preference is applied in `<head>` before Alpine.js boots (no wrong-theme flash).
- **Stale published config**: After `composer update`, Studio always shows the package model catalog and pricing even if the host app still has an older published `config/ai-hub.php`.

### Changed
- Studio version badge is now **v1.2.2**.

## [1.2.1] — 2026-08-23

### Added
- **Light & Dark Theme Toggle**: Studio UI (`/ai-hub`) now includes full light and dark mode with persistent user theme preferences in `localStorage`.
- **Inline Provider Card Save Buttons**: Direct instant `Save` button beside every provider card that highlights when credentials or models are modified.
- **Visual Saved State Badges**: Real-time status indicators on cards and default provider pills (`Saved & ready`, `Unsaved edits`, `No key added`, `Default`).
- **Zero-Worker Logging**: Added `after_response` lifecycle logging mode allowing request telemetry without requiring `php artisan queue:work` running.
- **August 2026 AI Frontier Models**: Flagship models and pricing for Gemini 3.7 Flash, GPT-5.6 series, Claude 3.7 Sonnet (Hybrid thinking), Grok 4 series, DeepSeek R1, etc.
- **Multi-tier Security & Access Control**: Built-in `AIHub::auth()` callback, `AI_HUB_ROLES`, `AI_HUB_EMAILS`, and `viewAiHub` Gate.
- **Single-Provider API Route**: Added `POST /api/provider` for saving single-provider settings instantly.
- **MassPrunable Support**: `AiRequestLog` now supports Laravel's automatic `model:prune` scheduler.

## [1.2.0] — 2026-08-23

### Added
- Complete August 2026 marketplace model update across all 9 AI providers:
  - **Gemini**: Added `gemini-3.7-flash`, `gemini-3.6-flash`, `gemini-3.1-pro`, `gemini-3.0-flash`, `gemini-3.0-pro`, `gemini-2.5-pro`, `gemini-2.5-flash`, `gemini-2.0-pro-exp-02-05`, `gemini-2.0-flash-thinking-exp-01-21`, `gemini-1.5-flash-8b`, and `text-embedding-004`, while retaining all `2.0` and `1.5` models.
  - **OpenAI**: Added `gpt-5.6-luna`, `gpt-5.6-terra`, `gpt-5.6-sol`, `gpt-5.5`, `gpt-5.4`, `gpt-5`, `gpt-5-mini`, `gpt-5-pro`, `o3`, `o3-mini`, `o4-mini`, `o1`, `o1-mini`, `o1-preview`, `chatgpt-4o-latest`, `gpt-4-turbo`, while retaining `gpt-4o-mini`, `gpt-4o`, `gpt-4.1`, and `gpt-4.1-mini`.
  - **Grok**: Added `grok-4.6`, `grok-4.5`, `grok-4.3`, `grok-4.1-fast`, `grok-3`, `grok-3-mini`, `grok-2-1212`, `grok-2-vision-1212`, while retaining `grok-2-latest`, `grok-2`, and `grok-beta`.
  - **Claude**: Added `claude-3-7-sonnet-latest` (hybrid reasoning), `claude-3-7-sonnet-20250219`, `claude-sonnet-4-20250514`, `claude-3-5-sonnet-20241022`, `claude-3-5-haiku-20241022`, `claude-3-opus-20240229`, `claude-3-haiku-20240307`, while retaining `claude-3-5-sonnet-latest`, `claude-3-5-haiku-latest`, `claude-3-opus-latest`.
  - **OpenRouter**: Added `google/gemini-3.7-flash`, `google/gemini-3.1-pro`, `google/gemini-3-flash`, `anthropic/claude-3.7-sonnet`, `anthropic/claude-3.7-sonnet:thinking`, `openai/gpt-5`, `openai/gpt-5-mini`, `openai/gpt-5-pro`, `openai/o3-mini`, `openai/o1`, and `x-ai/grok-2-1212`.
  - **DeepSeek**: Refined models & pricing for `deepseek-chat` (DeepSeek-V3), `deepseek-reasoner` (DeepSeek-R1), and `deepseek-coder`.
  - **Mistral**: Added `ministral-8b-latest`, `ministral-3b-latest`, `pixtral-large-latest`, `pixtral-12b-2409`, `open-codestral-mamba`, and `mistral-embed`.
  - **Groq**: Added `deepseek-r1-distill-llama-70b`, `deepseek-r1-distill-qwen-32b`, `llama-3.3-70b-specdec`, `qwen-2.5-32b`, `qwen-2.5-coder-32b`, `llama-3.2-11b-vision-preview`, `llama-3.2-90b-vision-preview`, `llama-3.2-3b-preview`, `llama-3.2-1b-preview`, and `llama-3.1-70b-versatile`.
  - **Ollama**: Added `llama3.3`, `deepseek-r1`, `qwen2.5-coder`, `qwen2.5`, `mistral-small`, `phi4`, `llama3.2-vision`, and `gemma2`.
- Updated comprehensive token pricing matrix in `config/ai-hub.php` with official standard rates per 1M tokens.
- **Built-in Route Authorization & Role-Based Access**:
  - Added `AuthorizeStudio` middleware for automatic protection of `/ai-hub` Studio UI and API routes.
  - Added `AIHub::auth(\Closure $callback)` for defining custom authorization callbacks (Horizon/Telescope style).
  - Added `AI_HUB_ROLES` and `AI_HUB_EMAILS` config/environment options for instant role-based access control.
  - Added automatic Laravel Gate (`viewAiHub`) resolution for team permissions.

## [1.1.1] — 2026-08-15

### Fixed
- Fixed model select dropdown initialization in Studio UI (`/ai-hub`) by populating options directly via Blade rather than template loops inside `<select>` tags.

### Added
- Added multi-middleware parsing (comma-separated strings and arrays) in `AiHubServiceProvider` to easily protect `/ai-hub` with authentication, roles, and gates via `.env` or config.
- Documented role & gate access control in `README.md`.

## [1.1.0] — 2026-07-23

### Added
- **DeepSeek**, **Mistral**, **Groq**, **Ollama**, and **OpenRouter** providers (OpenAI-compatible)
- Shared `OpenAICompatibleProvider` + `ProviderCatalog` for scalable provider lists
- Fluent shortcuts: `deepseek()`, `mistral()`, `groq()`, `ollama()`, `openrouter()`
- Popular models + pricing tables for the new providers
- Studio UI / CLI / settings store now cover all nine providers

### Changed
- Grok now uses the OpenAI-compatible driver path
- README, keywords, and package description updated for the expanded provider set

## [1.0.0] — 2026-07-22

### Added
- Initial release: OpenAI, Gemini, Claude, Grok
- Fluent `AIHub` API, retries, JSON recovery, cost calculator
- Async usage logging + analytics
- Visual studio at `/ai-hub` (keys, priority failover, analytics)
- `ai-hub:configure` Artisan command
- Optional Filament dashboard widget stub
