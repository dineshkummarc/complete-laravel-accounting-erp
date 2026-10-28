# Laravel AI Hub

<p align="center">
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/v/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="Latest Version"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/dt/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/imrandevbd/laravel-ai-hub"><img src="https://img.shields.io/packagist/php-v/imrandevbd/laravel-ai-hub.svg?style=flat-square" alt="PHP"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-teal.svg?style=flat-square" alt="License"></a>
  <a href="https://imrandev.bd"><img src="https://img.shields.io/badge/by-Imran%20Dev%20BD-14b8a6.svg?style=flat-square" alt="Imran Dev BD"></a>
</p>

**One unified interface. Thirteen AI providers. Playground, budgets, tools, vision, and cost telemetry.**

Swap **OpenAI**, **Gemini**, **Claude**, **Grok**, **DeepSeek**, **Mistral**, **Groq**, **Ollama**, **OpenRouter**, **Azure OpenAI**, **Together**, **Fireworks**, or **Perplexity** via config or a visual Studio UI — every request is cost-tracked in USD, retried with exponential backoff, JSON-repaired automatically, and logged asynchronously with zero user latency (no queue workers required).

Built by **[Imran Dev BD](https://imrandev.bd/)** · Laravel **10–13** · PHP **8.2+**

```bash
composer require imrandevbd/laravel-ai-hub
```

**Packagist:** [`imrandevbd/laravel-ai-hub`](https://packagist.org/packages/imrandevbd/laravel-ai-hub)  
**GitHub:** [github.com/imranbru99/laravel-ai-hub](https://github.com/imranbru99/laravel-ai-hub)

---

## Why Laravel AI Hub?

| Advantage | Benefit |
|---|---|
| **One Unified API** | Identical fluent chain across 13 providers including Azure OpenAI, Together, Fireworks & Perplexity |
| **Dynamic Model Routing** | Auto-detects providers from model IDs (`AIHub::model('gemini-3.8-flash')`) or scoped chains (`AIHub::priority(['gemini', 'openai'])`) |
| **Testing Assertion Engine** | Full mocking & assertions with `AIHub::fake()`, `assertSent()`, `assertNotSent()`, `assertSentCount()` |
| **Conversational Chat Sessions** | Multi-turn sessions via `AIHub::chat()` with history, export/load, and cumulative cost tracking |
| **Server-Sent Events (SSE)** | `streamResponse()` returns standard SSE stream ready for browsers and frontend apps |
| **Strict JSON Schemas** | `asJsonSchema()` & `AIHub::structured()` enforce structured outputs across OpenAI, Gemini & Claude |
| **Latest 2026 Models** | Google Gemini 3.8 / 2.5 series, OpenAI GPT-5.6 / GPT-4.5 / o3 / o1-pro, Claude 3.7 Sonnet & Opus 4, Grok 4.6, DeepSeek V3 / R1, Mistral Saba |
| **Hybrid Thinking Engine** | Fluent `->thinking()`, `->withoutThinking()`, and `->reasoningEffort()` across Gemini, Claude, and OpenAI |
| **Interactive Studio UI** | `/ai-hub` (Studio v1.7.1) — Playground with thinking controls, custom model IDs, light/dark theme, budgets, telemetry |
| **Automatic Failover** | `#1 → #2 → #3…` chain fallback when rate-limited or unavailable |
| **Tools, vision & cache** | Function tools, image inputs, named prompt templates, optional response cache |
| **Spend budgets** | Monthly / provider / job USD caps with block or warn |
| **Zero-Worker Logging** | `after_response` logging stores analytics immediately without requiring `php artisan queue:work` |
| **JSON Auto-Repair** | Depth-aware parser recovers malformed or truncated JSON from LLM responses automatically |
| **Cost & Health Analytics** | Real-time USD spend calculation, failure %, latency percentiles (p50/p95/p99) & top jobs |
| **Role & Gate Protection** | Built-in `AIHub::auth()` callback, `AI_HUB_ROLES` check, and Laravel Gate integration |

---

## Features at a Glance

| Feature | Description |
|---|---|
| **Multi-Provider Hub** | OpenAI · Gemini · Claude · Grok · DeepSeek · Mistral · Groq · Ollama · OpenRouter · Azure · Together · Fireworks · Perplexity |
| **Visual Studio** | `/ai-hub` with Playground, thinking budget controls, light/dark mode, per-provider save, budgets & templates |
| **Hybrid Thinking & Reasoning** | Gemini 3.8 / 3.7 `thinkingConfig`, Claude 3.7 extended thinking, and OpenAI o1/o3/gpt-5 `reasoning_effort` |
| **Failover Priority Chain** | Customize and drag-and-drop provider priority order (`#1` tried first) |
| **JSON Recovery** | Strips markdown code blocks and repairs broken/unclosed braces automatically |
| **Retries & Backoff** | Exponential backoff with jitter on 429 and 5xx errors |
| **Real-time Cost Tracking** | Precise USD pricing calculation per 1M tokens from built-in pricing tables |
| **Spend budgets** | Monthly, per-provider, and per-job USD caps (`block` or `warn`) |
| **Zero-Latency Logging** | Logs saved automatically via `after_response` lifecycle or background queue |
| **Telemetry Dashboard** | 30-day spend, failure rate, JSON recovery rate, latency p95, daily cost chart |
| **Filament Widget** | Optional ready-to-use widget for Filament v3/v4 admin panels |
| **Filament nav (new tab)** | Admin sidebar **AI Hub** opens `/ai-hub` Studio in a new browser tab |

---

## Installation

```bash
# 1. Install via Composer
composer require imrandevbd/laravel-ai-hub

# 2. Publish configuration and migrations
php artisan vendor:publish --tag=ai-hub-config
php artisan migrate
```

### Upgrading

```bash
composer update imrandevbd/laravel-ai-hub
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

Studio (`/ai-hub`) reads the package model catalog automatically after an upgrade. Re-publishing config is optional:

```bash
php artisan vendor:publish --tag=ai-hub-config --force
```

`--force` overwrites a previously published `config/ai-hub.php`. Skip it if you customized that file — new models still appear in the dropdown.

If Studio still shows the **old layout** (no Playground tab, no `Studio v1.7.1` badge), Laravel is using a published copy at `resources/views/vendor/ai-hub/studio.blade.php`. That file wins over the package. Either delete it, or refresh it:

```bash
php artisan vendor:publish --tag=ai-hub-views --force
php artisan view:clear
```

### Environment Configuration (`.env`)

```env
# Default Provider & Failover
AI_HUB_PROVIDER=gemini
AI_HUB_FAILOVER=true

# Security (Optional: Restrict /ai-hub studio access)
AI_HUB_ROLES="admin,developer"
AI_HUB_EMAILS="admin@yourdomain.com"
# AI_HUB_MIDDLEWARE="auth,role:admin"

# Provider API Keys
GEMINI_API_KEY=AIzaSy...
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
GROK_API_KEY=xai-...
DEEPSEEK_API_KEY=sk-...
MISTRAL_API_KEY=...
GROQ_API_KEY=gsk_...
OPENROUTER_API_KEY=sk-or-...
AZURE_OPENAI_API_KEY=...
AZURE_OPENAI_ENDPOINT=https://YOUR-RESOURCE.openai.azure.com
TOGETHER_API_KEY=...
FIREWORKS_API_KEY=...
PERPLEXITY_API_KEY=...
# OLLAMA_BASE_URL=http://127.0.0.1:11434/v1
# AI_HUB_BUDGET_MONTHLY=50
# AI_HUB_BUDGET_ON_EXCEED=block
```

> **Tip:** You can leave `.env` empty and configure all keys and models visually via the **`/ai-hub`** Studio UI. Keys are stored encrypted in the database.

---

## Visual Studio (`/ai-hub`) & Visual Telemetry

Visit **`/ai-hub`** in your browser after installing. The Studio provides a real-time visual control room for all 13 providers, credential management, interactive prompting, live streaming, and full financial cost telemetry.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ⚡ LARAVEL AI HUB STUDIO  [ Studio v1.7.1 ]                   Configured: [ ● 8 of 13 Providers Active ]   ☀ Light   │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  TABS:  [ Keys & Models ]    [ Playground ]    [ Priority Chain ]    [ Usage Analytics & Costing ]                    │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 1. Studio Dashboard — Provider Credentials, Models & Instant Save

Manage all 13 providers with masked encryption, instant per-card saving, live connection testing, and single-click default provider assignment.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ACTIVE DEFAULT PROVIDER:  ★ Tried #1 First                                              [✔] Automatic Failover       │
│                                                                                                                      │
│  [★ Google Gemini]       [● OpenAI]           [● Anthropic Claude]    [● xAI Grok]        [● DeepSeek]               │
│   gemini-4-argon          gpt-6-luna           claude-opus-5.5         grok-4.7            deepseek-v4.1-flash       │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                      │
│  ┌─ GOOGLE GEMINI ───────────── [★ DEFAULT] ─┐   ┌─ OPENAI ────────────────────── [● ACTIVE] ─┐                      │
│  │ Status: ● Saved & ready                   │   │ Status: ● Saved & ready                    │                      │
│  │ Key:    [AIzaSyB9••••••••••••••••••••]    │   │ Key:    [sk-proj-••••••••••••••••••••••••] │                      │
│  │ Model:  [gemini-4-argon             ▼]    │   │ Model:  [gpt-6-luna                      ▼]│                      │
│  │ Ping:   ✔ OK · 214ms · $0.000000          │   │ Ping:   ✔ OK · 186ms · $0.000000           │                      │
│  │                                           │   │                                            │                      │
│  │ [Test Connection]     [✔ Primary Default] │   │ [Test Connection]    [Set as #1 Default]   │                      │
│  └───────────────────────────────────────────┘   └────────────────────────────────────────────┘                      │
│                                                                                                                      │
│  ┌─ ANTHROPIC CLAUDE ────────── [● ACTIVE] ─┐   ┌─ MISTRAL AI ──────────── [● UNSAVED EDITS] ┐                      │
│  │ Status: ● Saved & ready                   │   │ Status: ⚠️ Modified (waiting to save)      │                      │
│  │ Key:    [sk-ant-•••••••••••••••••••••]    │   │ Key:    [sk-mist-••••••••••••••••••••••••] │                      │
│  │ Model:  [claude-opus-5.5            ▼]    │   │ Model:  [mistral-large-4                 ▼]│                      │
│  │ Ping:   ✔ OK · 298ms · $0.000000          │   │ Ping:   (not tested yet)                   │                      │
│  │                                           │   │                                            │                      │
│  │ [Test Connection]    [Set as #1 Default]  │   │ [Test Connection]    [💾 Save This Provider]│                      │
│  └───────────────────────────────────────────┘   └────────────────────────────────────────────┘                      │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

#### Key Dashboard Highlights:
- **Instant Per-Card Save**: Directly beside the API key and model selection on each provider card, an instant **`Save`** button lights up as soon as you type or change a model.
- **Floating Quick-Save Bar**: Slides up automatically if unsaved changes exist anywhere across cards, letting you sync everything in one click.
- **Masked Security**: Secret keys are never exposed in plaintext; values are encrypted with Laravel's `Crypt` engine in the database.
- **Light & Dark Theme**: Full theme toggle (`☀ Light` / `🌙 Dark`) with preference saved in `localStorage`.

---

### 2. Interactive Playground — Real-Time Streaming & Hybrid Reasoning Controls

Run live prompts, stream completions token-by-token over SSE, toggle thinking budgets, and inspect exact token usage and financial cost before deploying to production.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  PLAYGROUND: Test & Stream Models Live                                          [ Load Saved Prompt Template... ▼ ]  │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  PROVIDER              MODEL                  TEMPERATURE          MAX COMPLETION TOKENS                             │
│  [ Google Gemini    ▼] [ gemini-4-argon   ▼]  [ 0.7              ] [ 4,096                         ]                 │
│                                                                                                                      │
│  HYBRID REASONING & THINKING CONTROLS:                                                                               │
│  [✔] Enable Thinking Budget: [ 4,096 tokens ▼ ]   Reasoning Effort: [ High ▼ ]    Vision: [ https://...photo.png   ] │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  SYSTEM PROMPT:                                                                                                      │
│  ┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐  │
│  │ You are a senior Laravel performance architect and security auditor. Return structured markdown.              │  │
│  └────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│  USER MESSAGE:                                                                                                       │
│  ┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐  │
│  │ Audit this Eloquent query for N+1 issues and suggest an optimal chunked cursor implementation:                 │  │
│  │ $users = User::where('active', 1)->with('orders.items')->get();                                                │  │
│  └────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                                                      │
│  [ ⚡ Send Request ]    [ 🌊 Stream (SSE) ]    [ 💾 Save as Template ]                                                │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  LIVE RESPONSE STREAM                                                                                                │
│  gemini · gemini-4-argon · 342ms · $0.0028 USD · 1,248 tokens (Prompt: 210, Thinking: 810, Completion: 228)  [ Copy ]│
│  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────  │
│  ### Architectural Audit:                                                                                            │
│                                                                                                                      │
│  1. **Memory Ceiling Risk**: Loading nested relationships via `->get()` loads all hydrated models into memory.      │
│  2. **Recommended Cursor Stream**:                                                                                  │
│     ```php                                                                                                           │
│     User::query()                                                                                                    │
│         ->where('active', 1)                                                                                         │
│         ->with(['orders' => fn ($q) => $q->select('id', 'user_id', 'total_usd'), 'orders.items'])                    │
│         ->lazyById(250)                                                                                              │
│         ->each(function (User $user) {                                                                               │
│             // Process batch with constant memory consumption                                                        │
│         });                                                                                                          │
│     ```                                                                                                              │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 3. Conversational Multi-Turn Demo & Live Dialogue Simulation

Simulate persistent conversation sessions with automatic context retention, turn tracking, and cumulative session cost telemetry via `AIHub::chat()`:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  CONVERSATIONAL CHAT SESSION (3 Turns · Gemini 4 Argon · Total Cost: $0.0041 · 2,640 Tokens)                         │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                      │
│  👤 USER [Turn 1]:                                                                                                   │
│  "How do I structure a multi-tenant payment gateway in Laravel using Pest PHP?"                                      │
│                                                                                                                      │
│  🤖 ASSISTANT (Gemini 4 Argon · 284ms · $0.0011 · 640 tok):                                                         │
│  "Use an Action class with a tenant resolver interface. Here is how you can write the contract and mock it cleanly   │
│  using Pest PHP test suites..."                                                                                      │
│                                                                                                                      │
│  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────  │
│                                                                                                                      │
│  👤 USER [Turn 2] (Context retained automatically):                                                                  │
│  "Now convert that into an asynchronous queued job that handles Stripe webhooks."                                    │
│                                                                                                                      │
│  🤖 ASSISTANT (Gemini 4 Argon · 392ms · $0.0016 · 980 tok):                                                         │
│  "Here is `ProcessTenantWebhookJob` implementing `ShouldQueue` with exponential backoff and idempotency keys..."     │
│                                                                                                                      │
│  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────  │
│                                                                                                                      │
│  👤 USER [Turn 3]:                                                                                                   │
│  "Add failover protection if Stripe webhook signature verification fails."                                           │
│                                                                                                                      │
│  🤖 ASSISTANT (Gemini 4 Argon · 321ms · $0.0014 · 1,020 tok):                                                        │
│  "Updated with `SignatureVerificationException` handling, security audit logging, and automated dead-letter routing."│
│                                                                                                                      │
│  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────  │
│  SESSION TELEMETRY:  Cumulative Spend: $0.0041 USD · Turns: 3 · Active Model: gemini-4-argon · Status: Ready         │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### 4. Costing & Usage Analytics Dashboard

View full 30-day telemetry, live USD spend calculations, budget enforcement, latency percentiles, and per-job cost attribution.

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  USAGE ANALYTICS & COST TELEMETRY                                          [ Past 30 Days ▼ ]   [ ⟳ Refresh Stats ]  │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  SPEND BUDGET MONITOR                                                                                                │
│  Monthly Cap: $50.00 USD   │  Spent: $14.8219 (29.6%)   │  Remaining: $35.1781 USD   │  On Exceed: [ Block Requests ]│
│  [██████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]  $14.82 / $50.00 (Healthy)          │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                      │
│  ┌─ TOTAL SPEND (30D) ─────┐   ┌─ REQUEST SUCCESS ────────┐   ┌─ JSON AUTO-RECOVERY ───┐   ┌─ LATENCY (P95) ────────┐│
│  │  $14.8219 USD           │   │  99.84%                  │   │  4.82%                 │   │  384 ms                ││
│  │  1,842 total requests   │   │  3 failover recoveries   │   │  89 malformed repaired │   │  p50: 192ms · p99: 680 ││
│  └─────────────────────────┘   └──────────────────────────┘   └────────────────────────┘   └────────────────────────┘│
│                                                                                                                      │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  SPEND BY PROVIDER (Past 30 Days)                                                                                    │
│                                                                                                                      │
│  OpenAI (GPT-6 Luna & GPT-5.6)       $6.8410  [██████████████████████████░░░░░░░░░░░░░░░░]  46.1%  (920 requests)    │
│  Google Gemini (Argon & 3.8 Flash)   $4.2150  [████████████████░░░░░░░░░░░░░░░░░░░░░░░░░░]  28.4%  (640 requests)    │
│  Anthropic Claude (Opus 5.5)         $2.1800  [████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]  14.7%  (112 requests)    │
│  DeepSeek (V4.1 Flash & R1)          $1.1240  [████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]   7.6%  (138 requests)    │
│  xAI Grok (Grok 4.7)                 $0.4619  [██░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░]   3.2%   (32 requests)    │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  TOP TRACKED JOBS & WORKFLOWS (`->forJob(...)`)                                                                      │
│                                                                                                                      │
│  JOB NAME                     REQUESTS    AVG LATENCY    TOTAL TOKENS      TOTAL SPEND USD   BUDGET STATUS           │
│  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────  │
│  invoice-ocr-processing            540          412ms     1,824,000 tok        $4.1200 USD   Under cap ($10 max)     │
│  support-chat-agent                680          215ms       942,000 tok        $3.9510 USD   Under cap ($15 max)     │
│  code-security-audit               120          680ms     1,410,000 tok        $3.6420 USD   Under cap ($10 max)     │
│  sql-query-optimizer               310          184ms       480,000 tok        $1.8900 USD   Under cap ($5 max)      │
│  seo-metadata-generator            192          142ms       180,000 tok        $1.2189 USD   Under cap ($5 max)      │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

---

### Priority Chain & Automatic Failover

Configure provider priority in `/ai-hub` (drag-and-drop or click `Make #1`) or directly in `config/ai-hub.php`:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ACTIVE RUNTIME PRIORITY CHAIN (With Automatic Failover Enabled):                                                    │
│                                                                                                                      │
│  #1 Google Gemini  ──▶  #2 OpenAI  ──▶  #3 Claude  ──▶  #4 Grok  ──▶  #5 DeepSeek  ──▶  #6 Mistral ...               │
│                                                                                                                      │
│  ⚡ If Gemini experiences 429 rate limits or network timeout:                                                         │
│     ↳ Seamlessly switches to #2 OpenAI (normalizing model automatically) with ZERO application crashes.              │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### CLI Alternative (`ai-hub:configure`)

```bash
# Interactive configuration wizard
php artisan ai-hub:configure

# Direct provider setup
php artisan ai-hub:configure gemini --key=YOUR_KEY --model=gemini-4-argon --default
php artisan ai-hub:configure openai --key=YOUR_KEY --model=gpt-6-luna
php artisan ai-hub:configure deepseek --key=YOUR_KEY --model=deepseek-v4.1-flash

# View current masked configuration
php artisan ai-hub:configure --show
```

---

## Supported Providers & Latest 2026 Models

| Provider | Key | Flagship & Hybrid Reasoning Models |
|---|---|---|
| **Google Gemini** | `gemini` | `gemini-4-argon` (1M context frontier reasoning), `gemini-3.8-flash`, `gemini-3.8-pro`, `gemini-3.8-flash-lite`, `gemini-3.7-flash`, `gemini-3.7-pro`, `gemini-2.5-pro`, `gemini-2.5-flash`, `gemini-2.0-flash` |
| **OpenAI** | `openai` | `gpt-6-luna` ($0.10/1M ultra-fast), `gpt-6-sol`, `gpt-6-astra`, `gpt-6`, `gpt-5.6-luna`, `gpt-5.6-terra`, `gpt-5.6-sol`, `o3`, `o3-mini`, `o4-mini`, `o1`, `o1-pro`, `gpt-4.5`, `gpt-4o` |
| **Anthropic Claude** | `claude` | `claude-opus-5.5`, `claude-sonnet-5.5`, `claude-haiku-5.5`, `claude-fable-5.1`, `claude-opus-4.5`, `claude-3-7-sonnet-latest` (Extended Thinking), `claude-sonnet-4-latest`, `claude-haiku-4-latest` |
| **xAI Grok** | `grok` | `grok-4.7` (Flagship reasoning, 500k context), `grok-4.6`, `grok-4.5`, `grok-4.3` (1M context), `grok-4.20`, `grok-build-0.1`, `grok-4.1-fast`, `grok-3`, `grok-2` |
| **DeepSeek** | `deepseek` | `deepseek-v4.1-flash` (1M context multimodal), `deepseek-v4-pro`, `deepseek-v4`, `deepseek-chat` (V3), `deepseek-reasoner` (R1), `deepseek-coder` |
| **Mistral AI** | `mistral` | `mistral-large-4` (1M context frontier), `mistral-medium-3.5`, `mistral-small-4`, `mistral-saba-latest`, `codestral-2501`, `devstral-latest`, `ministral-14b-latest`, `pixtral-large-latest` |
| **Groq (LPU Speed)** | `groq` | `llama-3.3-70b-versatile`, `deepseek-r1-distill-llama-70b`, `deepseek-r1-distill-qwen-32b`, `qwen-2.5-coder-32b`, `llama-3.1-8b-instant` |
| **Ollama (Local)** | `ollama` | `llama3.3`, `llama3.2`, `deepseek-r1`, `deepseek-v3`, `qwen2.5-coder`, `qwen2.5`, `mistral-small`, `phi4`, `gemma2`, `llama3.2-vision` |
| **OpenRouter** | `openrouter` | `google/gemini-4-argon`, `openai/gpt-6-luna`, `anthropic/claude-sonnet-5.5`, `anthropic/claude-3.7-sonnet`, `deepseek/deepseek-v4.1-flash`, `x-ai/grok-4.7` |
| **Azure OpenAI** | `azure` | `gpt-6-luna`, `gpt-6`, `gpt-5`, `gpt-4.5-preview`, `gpt-4o`, `gpt-4o-mini`, `o3-mini`, `o1`, `o1-pro` |
| **Together** | `together` | `meta-llama/Llama-3.3-70B-Instruct-Turbo`, `Qwen/Qwen2.5-Coder-32B-Instruct`, `deepseek-ai/DeepSeek-R1`, `deepseek-ai/DeepSeek-V3` |
| **Fireworks** | `fireworks` | `accounts/fireworks/models/llama-v3p3-70b-instruct`, `accounts/fireworks/models/deepseek-r1`, `accounts/fireworks/models/deepseek-v3` |
| **Perplexity** | `perplexity` | `sonar-deep-research`, `sonar-reasoning-pro`, `sonar-reasoning`, `sonar-pro`, `sonar` |

---

## Usage Guide

```php
use ImranDevBd\AiHub\Facades\AIHub;

// 1. Basic Prompt (uses default provider & model)
$response = AIHub::prompt('Explain quantum computing in simple terms')->send();

echo $response->content;       // Generated text
echo $response->costUsd;       // Calculated token cost (e.g. 0.000412)
echo $response->latencyMs;     // Request duration (e.g. 312.4ms)
echo $response->totalTokens;   // Prompt + completion tokens

// 2. Specific Provider & Model Shortcuts
$response = AIHub::gemini('gemini-3.8-flash')
    ->prompt('Write a concise summary')
    ->send();

$response = AIHub::openai('gpt-5.6-luna')
    ->prompt('Write a high-performance database query')
    ->send();

$response = AIHub::claude('claude-3-7-sonnet-latest')
    ->prompt('Refactor this architecture')
    ->send();

$response = AIHub::deepseek('deepseek-reasoner')
    ->prompt('Solve this complex mathematical problem')
    ->send();
```

### Dynamic Model Selection & Purpose-Based Routing

A common real-world architecture in modern Laravel AI apps:
> **Scenario:** *"My application default is OpenAI (or OpenAI is #1 in priority), but for specific purposes like vision/OCR, 1M long-context documents, high-volume extraction, or low cost, I need to use Google Gemini (or Claude, DeepSeek). How do we handle this cleanly?"*

#### The Frontier Spotlight: OpenAI GPT Luna vs. Google Gemini 4 Argon

| Metric / Dimension | OpenAI GPT-6 Luna (`gpt-6-luna`) | Google Gemini 4 Argon (`gemini-4-argon`) |
|---|---|---|
| **Role & Architecture** | Ultra-fast, lightweight high-throughput daily driver | Flagship frontier reasoning & deep agentic workflow engine |
| **Token Pricing** | **\$0.10** input / **\$0.50** output per 1M tokens | **\$2.00** input / **\$10.00** output per 1M tokens |
| **Context Window** | 128,000 tokens | **1,000,000 tokens** (1M decode capacity) |
| **Best For** | High-volume chat, sub-second API classification, tagging, JSON extraction | Large repository refactoring, autonomous vulnerability patching, mathematical proofs |
| **Reasoning Control** | `->reasoningEffort('low'|'medium'|'high')` | `->thinking(4096)` hybrid reasoning budget |

```php
use ImranDevBd\AiHub\Facades\AIHub;

// Daily Driver: Ultra-fast, ultra-cheap GPT-6 Luna ($0.10 / $0.50 per 1M tokens)
$tag = AIHub::openai('gpt-6-luna')
    ->prompt('Classify this incoming email category in one word: "Can I get a tax invoice?"')
    ->send();

// Frontier Reasoning: Gemini 4 Argon with 1M context & hybrid thinking
$audit = AIHub::gemini('gemini-4-argon')
    ->thinking(8192)
    ->prompt('Analyze this entire multi-file codebase for race conditions, auth bypasses, and security flaws: ...')
    ->send();
```

---

#### 1. Direct Provider & Model Shortcut (Most Common)
You can call any provider at any time without altering your `.env` or global priority chain:
```php
use ImranDevBd\AiHub\Facades\AIHub;

// Global default remains OpenAI, but this specific task runs on Gemini
$response = AIHub::gemini('gemini-4-argon')
    ->prompt('Audit this contract for compliance risks')
    ->send();
```

#### 2. Smart Model Auto-Detection
Pass any model ID to `AIHub::model()`, and AI Hub automatically detects and selects the right driver:
```php
// Automatically routes to Gemini Argon (even when OpenAI is global default!)
$response = AIHub::model('gemini-4-argon')
    ->prompt('Analyze complex multi-step data')
    ->send();

// Automatically routes to OpenAI GPT-6 Luna
$response = AIHub::model('gpt-6-luna')
    ->prompt('Quickly categorize user feedback')
    ->send();

// Automatically routes to Claude
$response = AIHub::model('claude-3-7-sonnet-latest')
    ->prompt('Refactor this architecture cleanly')
    ->send();

// Automatically routes to DeepSeek
$response = AIHub::model('deepseek-reasoner')
    ->prompt('Solve this algorithmic puzzle')
    ->send();
```

#### 3. Unified `using()` Helper
Select both the provider and model in a single fluent call:
```php
$response = AIHub::using('gemini', 'gemini-3.8-pro')
    ->image('https://example.com/receipt.jpg')
    ->prompt('Itemize all line items and tax amounts on this receipt')
    ->send();
```

#### 4. Scoped Priority & Fallback Chains
Override the failover priority order for a specific task. Try Gemini first (e.g. for cost efficiency or speed); if Gemini hits a 429 rate limit or outage, safely fall back to OpenAI:
```php
$response = AIHub::priority(['gemini', 'openai'])
    ->prompt('Process high-volume user query')
    ->send();

// Or using the fallback() alias:
$response = AIHub::gemini('gemini-3.8-flash')
    ->fallback(['openai', 'claude'])
    ->prompt('Analyze customer sentiment')
    ->send();
```
> **Safe Model Normalization**: When failover triggers across providers, AI Hub automatically normalizes the model to the fallback provider's default model (e.g. `gpt-5.6-luna`), avoiding 404/400 errors from unrecognized model IDs.

#### 5. Isolate Purpose Without Failover
When a task strictly requires Gemini-specific features (e.g. Google search grounding or 2M context window), disable failover so it never falls back to an incompatible provider:
```php
$response = AIHub::gemini('gemini-3.8-flash')
    ->withoutFailover()
    ->image('https://example.com/schematic.png')
    ->prompt('Analyze this circuit diagram')
    ->send();
```

#### 6. Purpose-Specific Named Prompt Templates
Configure named prompt templates in Studio (`/ai-hub`) or DB. Each template can have its own dedicated provider and model:
- `receipt-ocr` &rarr; Provider: `gemini`, Model: `gemini-3.8-flash`
- `code-audit` &rarr; Provider: `claude`, Model: `claude-3-7-sonnet-latest`
- `chat-assistant` &rarr; Provider: `openai`, Model: `gpt-5.6-luna`

In your PHP code, simply call the template name:
```php
$response = AIHub::promptTemplate('receipt-ocr', ['receipt_text' => $rawText])->send();
```

#### 7. Purpose-Tagged Job Budgets & Observability
Tag requests with `forJob()` to monitor costs and enforce USD spend limits per purpose:
```php
$response = AIHub::gemini('gemini-3.8-flash')
    ->forJob('invoice-ocr')
    ->prompt('Extract invoice data')
    ->send();
```

#### 8. Multi-Turn Chat Sessions with Custom Routing
When managing multi-turn chat sessions (`AIHub::chat()`), target any provider and model:
```php
// Chat with Gemini while keeping OpenAI as your application's global default
$chat = AIHub::chat('gemini', 'gemini-3.8-flash')
    ->system('You are an expert document assistant.');

$reply = $chat->reply('Can you summarize this invoice?');
```

### Hybrid Thinking & Reasoning Engine

Seamlessly configure thinking token budgets on hybrid reasoning models (Google Gemini 3.8/3.7, Claude 3.7 Sonnet) or reasoning effort on OpenAI o-series / GPT-5:

```php
// 1. Google Gemini 3.8 with 4,096 thinking tokens
$response = AIHub::gemini('gemini-3.8-flash')
    ->thinking(4096)
    ->prompt('Solve this algorithmic challenge step-by-step')
    ->send();

// 2. Turn off thinking for ultra-fast, lowest latency responses
$response = AIHub::gemini('gemini-3.8-flash')
    ->withoutThinking()
    ->prompt('Return a quick greeting')
    ->send();

// 3. Anthropic Claude 3.7 Sonnet extended thinking
$response = AIHub::claude('claude-3-7-sonnet-latest')
    ->thinking(2048)
    ->prompt('Analyze this distributed database failure scenario')
    ->send();

// 4. OpenAI o3-mini or GPT-5 with reasoning effort
$response = AIHub::openai('o3-mini')
    ->reasoningEffort('high')
    ->prompt('Verify this formal mathematical proof')
    ->send();
```

### JSON Auto-Recovery (Fixes Malformed LLM JSON)

LLMs often wrap JSON in markdown blocks or cut off closing brackets. `recoverJson()` automatically repairs the syntax:

```php
$data = AIHub::gemini()
    ->prompt('Return a user profile JSON object')
    ->recoverJson()
    ->send()
    ->json(); // Returns clean PHP array
```

### Structured Outputs & Strict JSON Schemas

Enforce valid, structured JSON conforming to your custom schema across OpenAI, Azure, Gemini, and Claude:

```php
$schema = [
    'type' => 'object',
    'properties' => [
        'sentiment' => ['type' => 'string', 'enum' => ['positive', 'negative', 'neutral']],
        'confidence' => ['type' => 'number'],
        'key_topics' => ['type' => 'array', 'items' => ['type' => 'string']],
    ],
    'required' => ['sentiment', 'confidence', 'key_topics'],
    'additionalProperties' => false,
];

// 1. Fluent chain
$response = AIHub::openai('gpt-5.6-luna')
    ->asJsonSchema($schema, 'SentimentAnalysis', 'Classify customer review sentiment')
    ->prompt('The product shipped extremely fast and works wonderfully!')
    ->generate();

$data = $response->json(); // ['sentiment' => 'positive', 'confidence' => 0.99, ...]

// 2. Shortcut helper
$response = AIHub::structured($schema)
    ->prompt('Customer feedback text...')
    ->generate();
```

### Server-Sent Events (SSE) & Browser Streaming

Stream AI completions directly into browser frontend apps (Fetch API, EventSource, or Inertia.js):

```php
// In a Laravel Controller
public function stream(Request $request)
{
    return AIHub::gemini('gemini-3.8-flash')
        ->prompt($request->input('prompt'))
        ->streamResponse();
}

// Or consume stream chunks using a closure
AIHub::openai()->prompt('Generate report')->streamRaw(function (string $chunk) {
    echo $chunk;
});

// Generator loop
foreach (AIHub::openai()->prompt('Write essay')->stream() as $chunk) {
    echo $chunk;
}
```

### Multi-turn Conversational Chat Sessions (`AIHub::chat()`)

Manage persistent, multi-turn dialogue sessions with automatic context retention, turn tracking, and accumulated token cost calculation:

```php
use ImranDevBd\AiHub\Facades\AIHub;

// Initialize a session (optionally specify provider and model)
$chat = AIHub::chat('claude', 'claude-3-7-sonnet-latest')
    ->system('You are an expert Laravel and Pest PHP architect.');

// Turn 1
$reply1 = $chat->send('How do I mock external APIs cleanly in Pest?');
echo $reply1->content;

// Turn 2 (Context of Turn 1 is automatically preserved)
$reply2 = $chat->send('Can you convert that snippet into an architectural action class?');
echo $reply2->content;

// Cumulative token & cost telemetry
echo $chat->totalTokens(); // Total tokens across all turns
echo $chat->totalCost();   // Total USD cost across all turns

// Stream a chat turn directly as SSE to browser
return $chat->streamResponse('Explain how it works step-by-step');

// Export or restore chat history (e.g. for database persistence)
$history = $chat->export(); // Array of messages, system prompt, tokens, etc.
$chat->load($history);
```

### Testing Assertion Engine (`AIHub::fake()`)

Mock AI responses and assert outbound requests with Laravel-style testing syntax without hitting real APIs or incurring costs:

```php
use ImranDevBd\AiHub\Facades\AIHub;

public function test_user_summary_generation(): void
{
    // 1. Mock a simple string response
    AIHub::fake('This is a simulated AI summary.');

    // Or mock with sequential responses or per-provider responses:
    // AIHub::fake([
    //     'gemini' => 'Gemini simulated response',
    //     'openai' => AIHub::response(['key' => 'value']),
    // ]);

    // Execute your application code
    $response = AIHub::gemini('gemini-3.8-flash')
        ->prompt('Summarize account history')
        ->send();

    $this->assertSame('This is a simulated AI summary.', $response->content);

    // Assert that a matching request was dispatched
    AIHub::assertSent(function ($request) {
        return $request->getProvider() === 'gemini'
            && str_contains($request->getPrompt(), 'Summarize account history');
    });

    AIHub::assertSentCount(1);
    AIHub::assertNotSent(fn ($req) => $req->getProvider() === 'openai');
}
```

### Tools, vision, cache, and prompt templates

```php
// Vision
$response = AIHub::openai()
    ->prompt('Describe this image')
    ->image('https://example.com/photo.jpg')
    ->send();

// Function tools (OpenAI-style schema; mapped for Claude & Gemini)
$response = AIHub::openai()->tools([[
    'type' => 'function',
    'function' => [
        'name' => 'get_weather',
        'description' => 'Get the weather for a city',
        'parameters' => [
            'type' => 'object',
            'properties' => [
                'city' => ['type' => 'string'],
            ],
            'required' => ['city'],
        ],
    ],
]])->prompt('Weather in Dhaka?')->send();

$calls = $response->toolCalls; // run the tool, then continue with messages()

// Response cache (hits log type=cache_hit at $0)
$response = AIHub::gemini()->prompt($faq)->cache(3600)->send();

// Named templates saved in Studio Playground ({ticket} is interpolated)
$response = AIHub::promptTemplate('support.reply', ['ticket' => $body])->send();

// New providers
AIHub::azure('gpt-4o')->prompt('Hello from Azure')->send();
AIHub::together()->prompt('Hello from Together')->send();
AIHub::fireworks()->prompt('Hello from Fireworks')->send();
AIHub::perplexity('sonar-pro')->prompt('What shipped this week?')->send();
```

### Vector Embeddings

```php
$vector = AIHub::openai()
    ->model('text-embedding-3-small')
    ->embed('Laravel AI Hub semantic search')
    ->first(); // Returns float array vector
```

### Tagging Workflows & Background Jobs

Tag your requests to trace spend by feature in Analytics:

```php
AIHub::gemini()
    ->forJob('invoice-ocr')
    ->prompt($invoicePrompt)
    ->send();
```

---

## Failover Priority Chain

Configure provider priority in `/ai-hub` or in `config/ai-hub.php`:

```php
'priority' => [
    'gemini',      // #1 Tried first
    'openai',      // #2 If Gemini is rate-limited or down
    'claude',      // #3
    'grok',        // #4
    'deepseek',    // #5
    'mistral',     // #6
    'groq',        // #7
    'ollama',      // #8
    'openrouter',  // #9
    'azure',       // #10
    'together',    // #11
    'fireworks',   // #12
    'perplexity',  // #13
],
'failover_enabled' => true,
```

```
Runtime execution:
#1 Gemini fails (429 Rate Limit) -> Auto Failover -> #2 OpenAI succeeds!
```

---

## How Logging Works (Zero Queue Worker Required)

The package captures comprehensive telemetry without slowing down your user's web requests:

```php
// config/ai-hub.php
'logging' => [
    'enabled' => env('AI_HUB_LOGGING', true),
    'async' => env('AI_HUB_LOGGING_ASYNC', 'after_response'), // 'after_response', 'queue', or 'sync'
    'prune_days' => 90, // Automatic log pruning
],
```

| Mode | Worker Needed? | User Latency | Description |
|---|:---:|:---:|---|
| **`after_response`** *(Default)* | **No** ❌ | **0ms** | Executes database logging immediately after HTTP response is returned to browser. |
| **`queue`** | **Yes** ✅ | **0ms** | Pushes `TrackAiUsageJob` to your background queue (Redis/Database/Horizon). |
| **`sync`** | **No** ❌ | ~1-2ms | Synchronous direct database insert (ideal for testing/CLI). |

### Automatic Log Pruning

`AiRequestLog` uses Laravel's `MassPrunable` trait. Clean up old logs automatically in your scheduler (`routes/console.php`):

```php
use ImranDevBd\AiHub\Models\AiRequestLog;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune', ['--model' => [AiRequestLog::class]])->daily();
```

---

## Security & Access Control for `/ai-hub`

The package includes built-in `AuthorizeStudio` middleware to protect `/ai-hub` Studio from unauthorized access.

### 1. Dedicated Callback (`AIHub::auth`) — *Recommended*

In your `app/Providers/AppServiceProvider.php` (or `AuthServiceProvider.php`):

```php
use ImranDevBd\AiHub\Facades\AIHub;

public function boot(): void
{
    AIHub::auth(function ($request) {
        return app()->environment('local') ||
               ($request->user() && in_array($request->user()->email, ['admin@yourdomain.com'])) ||
               ($request->user() && $request->user()->hasRole('admin'));
    });
}
```

### 2. Role & Email Protection via `.env`

```env
# Automatically verifies $user->hasRole(), $user->role, or is_admin
AI_HUB_ROLES="admin,super-admin,developer"

# Optional: Restrict to specific admin emails
AI_HUB_EMAILS="lead@company.com,devops@company.com"
```

### 3. Laravel Gate (`viewAiHub`)

```php
use App\Models\User;
use Illuminate\Support\Facades\Gate;

public function boot(): void
{
    Gate::define('viewAiHub', function (User $user) {
        return in_array($user->role, ['admin', 'developer']);
    });
}
```

### 4. Custom Middleware Stack via `.env`

```env
AI_HUB_MIDDLEWARE="auth,role:admin"
```

- **Guests**: Redirected to `/login` (or 403 for API calls).
- **Unauthorized users**: Blocked with `403 Forbidden`.
- **Local Environment**: Automatically enabled during local development.

---

## Filament admin

When Filament is installed, the panel sidebar gets an **AI Hub** item that opens Studio (`/ai-hub`) in a **new tab**. Dashboard widget stats do the same.

```php
use ImranDevBd\AiHub\Filament\AiHubPlugin;

$panel->plugin(AiHubPlugin::make()); // optional — auto-registered if omitted
```

```env
AI_HUB_FILAMENT=true
AI_HUB_FILAMENT_NEW_TAB=true
# AI_HUB_FILAMENT_GROUP="Settings"
```

---

## Analytics API

Access aggregated telemetry programmatically:

```php
// 30-day overview summary
$summary = AIHub::analytics()->summary(now()->subDays(30));
// ['total_cost_usd' => 12.45, 'requests' => 1420, 'failure_rate' => 0.4, 'json_recovery_rate' => 3.2]

// Breakdown of spend per provider
$costs = AIHub::analytics()->costByProvider();

// Latency percentiles
$latency = AIHub::analytics()->latencyPercentiles('gemini');
// ['p50' => 240, 'p95' => 580, 'p99' => 890]

// Top tracked jobs by token volume
$topJobs = AIHub::analytics()->topJobs(10);

// Daily spending trend
$daily = AIHub::analytics()->dailyCost(30);
```

---

## License

MIT © [Imran Dev BD](https://imrandev.bd/)

---

## Developed by Imran Dev BD

| Platform | Link |
|---|---|
| Portfolio | [imrandev.bd](https://imrandev.bd/) |
| LinkedIn | [linkedin.com/in/imranbru99](https://linkedin.com/in/imranbru99) |
| GitHub | [github.com/imranbru99](https://github.com/imranbru99) |
| X / Twitter | [@imrandev_bd](https://x.com/imrandev_bd) |
| YouTube | [@ImranDevBD](https://youtube.com/@ImranDevBD) |
| Facebook | [ExpertImranDev](https://facebook.com/ExpertImranDev) |
| WhatsApp | [+880 1576-918420](https://wa.me/8801576918420) |
| Email | [me@imrandev.bd](mailto:me@imrandev.bd) |
| All Links | [linktr.ee/ExpertImranDev](https://linktr.ee/ExpertImranDev) |

**Need help or have questions? Contact → [imrandev.bd/contact](https://imrandev.bd/contact)**
