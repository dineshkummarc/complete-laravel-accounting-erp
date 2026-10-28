<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Exceptions\AiCatalogException;
use Illuminate\Support\Facades\Http;

class VoiceTranscriber
{
    public function transcribe(string $path): string
    {
        if (! is_file($path)) {
            throw new AiCatalogException("Voice file not found: {$path}");
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (in_array($ext, ['txt', 'md', 'json'], true)) {
            return trim((string) file_get_contents($path));
        }

        $key = config('ai-catalog.voice.api_key') ?: config('ai-hub.providers.openai.api_key');
        if (! is_string($key) || $key === '' || str_starts_with($key, 'test-')) {
            throw new AiCatalogException('Voice transcription needs OPENAI_API_KEY (or pass a .txt transcript in tests).');
        }

        $endpoint = (string) config('ai-catalog.voice.endpoint');
        $response = Http::timeout(60)
            ->withToken($key)
            ->attach('file', (string) file_get_contents($path), basename($path))
            ->post($endpoint, array_filter([
                'model' => config('ai-catalog.voice.model', 'whisper-1'),
                'language' => config('ai-catalog.voice.language'),
            ]));

        if (! $response->successful()) {
            throw new AiCatalogException('Voice transcription failed: '.$response->body());
        }

        return trim((string) ($response->json('text') ?? ''));
    }
}
