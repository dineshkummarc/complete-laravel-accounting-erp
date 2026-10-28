<?php

namespace ImranDevBd\AiHub\Tests;

use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AiHub\Support\CostCalculator;
use ImranDevBd\AiHub\Support\ProviderCatalog;

class ModelRoutingAndSelectionTest extends TestCase
{
    public function test_detects_provider_from_model_name(): void
    {
        $this->assertSame('gemini', ProviderCatalog::detectProvider('gemini-4-argon'));
        $this->assertSame('gemini', ProviderCatalog::detectProvider('gemini-argon'));
        $this->assertSame('gemini', ProviderCatalog::detectProvider('argon'));
        $this->assertSame('gemini', ProviderCatalog::detectProvider('gemini-3.8-flash'));
        $this->assertSame('gemini', ProviderCatalog::detectProvider('gemini-2.5-flash'));
        $this->assertSame('gemini', ProviderCatalog::detectProvider('gemini-1.5-pro'));

        $this->assertSame('claude', ProviderCatalog::detectProvider('claude-sonnet-5.5'));
        $this->assertSame('claude', ProviderCatalog::detectProvider('claude-opus-5.5'));
        $this->assertSame('claude', ProviderCatalog::detectProvider('claude-3-7-sonnet-latest'));
        $this->assertSame('claude', ProviderCatalog::detectProvider('claude-opus-4-latest'));
        $this->assertSame('claude', ProviderCatalog::detectProvider('claude-3-5-haiku-latest'));

        $this->assertSame('openai', ProviderCatalog::detectProvider('gpt-6-luna'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('gpt-6-sol'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('gpt-6-astra'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('gpt-6'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('luna'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('gpt-5.6-luna'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('gpt-4o'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('gpt-4.5-preview'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('o3-mini'));
        $this->assertSame('openai', ProviderCatalog::detectProvider('o1-pro'));

        $this->assertSame('grok', ProviderCatalog::detectProvider('grok-4.7'));
        $this->assertSame('grok', ProviderCatalog::detectProvider('grok-4.1-fast'));
        $this->assertSame('grok', ProviderCatalog::detectProvider('grok-2'));

        $this->assertSame('deepseek', ProviderCatalog::detectProvider('deepseek-v4.1-flash'));
        $this->assertSame('deepseek', ProviderCatalog::detectProvider('deepseek-chat'));
        $this->assertSame('deepseek', ProviderCatalog::detectProvider('deepseek-reasoner'));
        $this->assertSame('deepseek', ProviderCatalog::detectProvider('deepseek-v3'));
        $this->assertSame('deepseek', ProviderCatalog::detectProvider('deepseek-r1'));

        $this->assertSame('mistral', ProviderCatalog::detectProvider('mistral-large-4'));
        $this->assertSame('mistral', ProviderCatalog::detectProvider('mistral-large-latest'));
        $this->assertSame('mistral', ProviderCatalog::detectProvider('codestral-2501'));
        $this->assertSame('mistral', ProviderCatalog::detectProvider('devstral-latest'));
        $this->assertSame('mistral', ProviderCatalog::detectProvider('mistral-saba-latest'));

        $this->assertSame('perplexity', ProviderCatalog::detectProvider('sonar-pro'));
        $this->assertSame('perplexity', ProviderCatalog::detectProvider('sonar-deep-research'));

        $this->assertSame('together', ProviderCatalog::detectProvider('meta-llama/Llama-3.3-70B-Instruct-Turbo'));
        $this->assertSame('fireworks', ProviderCatalog::detectProvider('accounts/fireworks/models/deepseek-v3'));
        $this->assertSame('openrouter', ProviderCatalog::detectProvider('google/gemini-3.8-flash'));
    }

    public function test_aihub_model_auto_detects_provider_when_unlocked(): void
    {
        // Default is openai
        $req1 = AIHub::model('gemini-3.8-flash');
        $this->assertSame('gemini', $req1->getProvider());
        $this->assertSame('gemini-3.8-flash', $req1->getModel());

        $req2 = AIHub::model('claude-3-7-sonnet-latest');
        $this->assertSame('claude', $req2->getProvider());
        $this->assertSame('claude-3-7-sonnet-latest', $req2->getModel());

        $req3 = AIHub::model('deepseek-reasoner');
        $this->assertSame('deepseek', $req3->getProvider());
        $this->assertSame('deepseek-reasoner', $req3->getModel());
    }

    public function test_explicit_provider_locks_provider_even_with_cross_provider_model(): void
    {
        // Using OpenRouter to route a Gemini model
        $req = AIHub::provider('openrouter')->model('google/gemini-3.8-flash');
        $this->assertSame('openrouter', $req->getProvider());
        $this->assertSame('google/gemini-3.8-flash', $req->getModel());
    }

    public function test_aihub_using_sets_provider_and_model(): void
    {
        $req = AIHub::using('gemini', 'gemini-3.8-flash');
        $this->assertSame('gemini', $req->getProvider());
        $this->assertSame('gemini-3.8-flash', $req->getModel());
    }

    public function test_aihub_priority_and_fallback(): void
    {
        $req = AIHub::priority(['gemini', 'openai', 'claude']);
        $this->assertSame('gemini', $req->getProvider());
        $this->assertSame(['gemini', 'openai', 'claude'], $req->getPriority());

        $reqFallback = AIHub::fallback(['claude', 'gemini']);
        $this->assertSame('claude', $reqFallback->getProvider());
        $this->assertSame(['claude', 'gemini'], $reqFallback->getPriority());
    }

    public function test_conversation_smart_model_detection_and_using(): void
    {
        $chat = AIHub::chat(model: 'gemini-3.8-flash');
        $pending = $chat->buildPendingRequest();
        $this->assertSame('gemini', $pending->getProvider());
        $this->assertSame('gemini-3.8-flash', $pending->getModel());

        $chat2 = AIHub::chat();
        $chat2->using('claude', 'claude-3-7-sonnet-latest');
        $pending2 = $chat2->buildPendingRequest();
        $this->assertSame('claude', $pending2->getProvider());
        $this->assertSame('claude-3-7-sonnet-latest', $pending2->getModel());
    }

    public function test_cross_provider_failover_model_resolution(): void
    {
        $req = AIHub::gemini('gemini-3.8-flash');

        $method = new \ReflectionMethod($req, 'resolveModelForProvider');
        $method->setAccessible(true);

        // Chain index 0: should keep custom model gemini-3.8-flash
        $primaryModel = $method->invoke($req, 'gemini', 0);
        $this->assertSame('gemini-3.8-flash', $primaryModel);

        // Chain index 1 on openai: gemini-3.8-flash is NOT supported by openai, so fallback to openai default model
        $fallbackModel = $method->invoke($req, 'openai', 1);
        $this->assertSame(config('ai-hub.defaults.openai'), $fallbackModel);

        // If the custom model happens to be supported by the next provider (e.g. openrouter with slash model)
        $reqOpenRouter = AIHub::provider('together')->model('deepseek-ai/DeepSeek-V3');
        $openRouterModel = $method->invoke($reqOpenRouter, 'openrouter', 1);
        $this->assertSame('deepseek-ai/DeepSeek-V3', $openRouterModel);
    }

    public function test_calculates_pricing_for_latest_models(): void
    {
        $config = require __DIR__ . '/../config/ai-hub.php';
        $calculator = CostCalculator::fromConfig($config['pricing']);

        // gpt-4.5: 75 + 150 = 225
        $costGpt45 = $calculator->calculate('openai', 'gpt-4.5', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(225.00, $costGpt45, 0.0001);

        // o1-pro: 20 + 80 = 100
        $costO1Pro = $calculator->calculate('openai', 'o1-pro', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(100.00, $costO1Pro, 0.0001);

        // gemini-2.5-flash-lite: 0.05 + 0.20 = 0.25
        $costGeminiLite = $calculator->calculate('gemini', 'gemini-2.5-flash-lite', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(0.25, $costGeminiLite, 0.0001);

        // mistral-saba-latest: 0.20 + 0.60 = 0.80
        $costMistralSaba = $calculator->calculate('mistral', 'mistral-saba-latest', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(0.80, $costMistralSaba, 0.0001);

        // deepseek-v3: 0.27 + 1.10 = 1.37
        $costDeepSeekV3 = $calculator->calculate('deepseek', 'deepseek-v3', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(1.37, $costDeepSeekV3, 0.0001);

        // gemini-4-argon: 2.00 + 10.00 = 12.00
        $costArgon = $calculator->calculate('gemini', 'gemini-4-argon', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(12.00, $costArgon, 0.0001);

        // gpt-6-luna: 0.10 + 0.50 = 0.60
        $costGpt6Luna = $calculator->calculate('openai', 'gpt-6-luna', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(0.60, $costGpt6Luna, 0.0001);

        // claude-sonnet-5.5: 3.00 + 15.00 = 18.00
        $costClaudeSonnet55 = $calculator->calculate('claude', 'claude-sonnet-5.5', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(18.00, $costClaudeSonnet55, 0.0001);

        // grok-4.7: 3.00 + 15.00 = 18.00
        $costGrok47 = $calculator->calculate('grok', 'grok-4.7', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(18.00, $costGrok47, 0.0001);

        // deepseek-v4.1-flash: 0.15 + 0.60 = 0.75
        $costDeepSeekV4 = $calculator->calculate('deepseek', 'deepseek-v4.1-flash', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(0.75, $costDeepSeekV4, 0.0001);

        // mistral-large-4: 2.00 + 6.00 = 8.00
        $costMistralLarge4 = $calculator->calculate('mistral', 'mistral-large-4', 1_000_000, 1_000_000);
        $this->assertEqualsWithDelta(8.00, $costMistralLarge4, 0.0001);
    }
}
