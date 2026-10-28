<?php

namespace ImranDevBd\AiHub\Support;

class ProviderCatalog
{
    /**
     * All built-in provider keys (order = default priority suggestion).
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(config('ai-hub.providers', [
            'openai' => [],
            'gemini' => [],
            'claude' => [],
            'grok' => [],
            'deepseek' => [],
            'mistral' => [],
            'groq' => [],
            'ollama' => [],
            'openrouter' => [],
            'azure' => [],
            'together' => [],
            'fireworks' => [],
            'perplexity' => [],
        ]));
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'openai' => 'OpenAI',
            'gemini' => 'Gemini',
            'claude' => 'Claude',
            'grok' => 'Grok',
            'deepseek' => 'DeepSeek',
            'mistral' => 'Mistral',
            'groq' => 'Groq',
            'ollama' => 'Ollama',
            'openrouter' => 'OpenRouter',
            'azure' => 'Azure OpenAI',
            'together' => 'Together',
            'fireworks' => 'Fireworks',
            'perplexity' => 'Perplexity',
        ];
    }

    public static function label(string $key): string
    {
        return self::labels()[$key] ?? ucfirst($key);
    }

    public static function validationRule(): string
    {
        return 'in:'.implode(',', self::keys());
    }

    /**
     * Intelligently detect the intended AI provider from a model identifier.
     */
    public static function detectProvider(string $model): ?string
    {
        $model = trim($model);
        if ($model === '') {
            return null;
        }

        $lower = strtolower($model);

        // OpenRouter / Together / Fireworks prefixed identifiers
        if (str_starts_with($lower, 'accounts/fireworks/')) {
            return 'fireworks';
        }

        if (str_starts_with($lower, 'meta-llama/') || str_starts_with($lower, 'qwen/') || str_starts_with($lower, 'deepseek-ai/')) {
            return 'together';
        }

        if (str_contains($lower, '/')) {
            return 'openrouter';
        }

        // Direct provider prefixes
        if (str_starts_with($lower, 'gemini-') || $lower === 'argon' || str_contains($lower, 'argon')) {
            return 'gemini';
        }

        if (str_starts_with($lower, 'claude-')) {
            return 'claude';
        }

        if (str_starts_with($lower, 'grok-')) {
            return 'grok';
        }

        if (str_starts_with($lower, 'deepseek-')) {
            return 'deepseek';
        }

        if (str_starts_with($lower, 'mistral-')
            || str_starts_with($lower, 'ministral-')
            || str_starts_with($lower, 'pixtral-')
            || str_starts_with($lower, 'codestral')
            || str_starts_with($lower, 'devstral')
            || str_starts_with($lower, 'open-mistral-')
            || str_starts_with($lower, 'open-codestral-')) {
            return 'mistral';
        }

        if (str_starts_with($lower, 'sonar-')) {
            return 'perplexity';
        }

        if (str_starts_with($lower, 'gpt-')
            || str_starts_with($lower, 'chatgpt-')
            || str_starts_with($lower, 'text-embedding-3-')
            || $lower === 'luna'
            || (bool) preg_match('/^o[1-4](?:-|$)/', $lower)) {
            return 'openai';
        }

        if (str_starts_with($lower, 'llama-') || str_starts_with($lower, 'mixtral-') || str_starts_with($lower, 'gemma2-')) {
            return 'groq';
        }

        if (str_starts_with($lower, 'llama3') || str_starts_with($lower, 'phi') || str_starts_with($lower, 'codellama')) {
            return 'ollama';
        }

        // Check configured popular models map
        $popular = (array) config('ai-hub.popular_models', []);
        foreach ($popular as $provider => $models) {
            if (in_array($model, (array) $models, true)) {
                return (string) $provider;
            }
        }

        return null;
    }

    /**
     * Check whether a given model identifier is supported or expected by a provider.
     */
    public static function isModelSupportedByProvider(string $provider, string $model): bool
    {
        $detected = self::detectProvider($model);
        if ($detected === $provider) {
            return true;
        }

        // Popular models check
        $popular = (array) config("ai-hub.popular_models.{$provider}", []);
        if (in_array($model, $popular, true)) {
            return true;
        }

        // Pricing table check
        $pricing = (array) config("ai-hub.pricing.{$provider}", []);
        if (isset($pricing[$model])) {
            return true;
        }

        // Azure supports OpenAI models
        if ($provider === 'azure' && self::detectProvider($model) === 'openai') {
            return true;
        }

        // OpenRouter supports any slash-prefixed multi-provider ID
        if ($provider === 'openrouter' && str_contains($model, '/')) {
            return true;
        }

        return false;
    }
}
