<?php

namespace ImranDevBd\AiCatalog\Support;

use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use Illuminate\Support\Facades\Http;

class WebhookDispatcher
{
    public function send(string $event, CatalogGeneration $generation, array $extra = []): void
    {
        $urls = array_filter((array) config('ai-catalog.webhooks', []));
        if ($urls === []) {
            return;
        }

        $payload = array_merge([
            'event' => $event,
            'generation_id' => $generation->id,
            'uuid' => $generation->uuid,
            'status' => $generation->status?->value ?? $generation->status,
            'occurred_at' => now()->toIso8601String(),
        ], $extra);

        $secret = (string) config('ai-catalog.webhook_secret', '');
        $body = json_encode($payload) ?: '{}';
        $headers = ['Content-Type' => 'application/json'];
        if ($secret !== '') {
            $headers['X-Ai-Catalog-Signature'] = hash_hmac('sha256', $body, $secret);
        }

        foreach ($urls as $url) {
            try {
                Http::timeout(10)->withHeaders($headers)->withBody($body, 'application/json')->post((string) $url);
            } catch (\Throwable) {
                // Webhook failure must never block catalog flow.
            }
        }
    }
}
