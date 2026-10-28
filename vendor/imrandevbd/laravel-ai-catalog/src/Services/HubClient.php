<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiHub\Data\AiResponse;
use ImranDevBd\AiHub\Facades\AIHub;
use ImranDevBd\AiHub\PendingRequest;

class HubClient
{
    public function complete(
        array $schema,
        string $system,
        string $prompt,
        GenerationInput $input,
        string $job = 'ai-catalog.generate',
    ): AiResponse {
        $request = $this->baseRequest($input, $job)
            ->system($system)
            ->prompt($prompt)
            ->asJsonSchema($schema, 'ProductListing')
            ->recoverJson();

        if ($input->image) {
            $request->image($this->normalizeImage($input->image));
        }

        return $request->generate();
    }

    public function completeText(string $system, string $prompt, GenerationInput $input, string $job = 'ai-catalog.generate'): AiResponse
    {
        return $this->baseRequest($input, $job)
            ->system($system)
            ->prompt($prompt)
            ->generate();
    }

    protected function baseRequest(GenerationInput $input, string $job): PendingRequest
    {
        $provider = config('ai-catalog.provider');
        $model = config('ai-catalog.model');

        $request = $provider
            ? AIHub::provider((string) $provider)
            : AIHub::provider();

        if (is_string($model) && $model !== '') {
            $request->model($model);
        }

        $request->forJob($job)->meta([
            'package' => 'laravel-ai-catalog',
            'mode' => $input->mode->value,
            'locale' => $input->locale,
            'tenant_id' => $input->tenantId,
        ]);

        $ttl = config('ai-catalog.cache.enabled') ? (int) config('ai-catalog.cache.ttl', 0) : 0;
        if ($ttl > 0) {
            $request->cache($ttl);
        }

        return $request;
    }

    protected function normalizeImage(string $image): string
    {
        if (str_starts_with($image, 'http') || str_starts_with($image, 'data:')) {
            return $image;
        }

        if (is_file($image)) {
            $binary = file_get_contents($image);
            $mime = mime_content_type($image) ?: 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode((string) $binary);
        }

        return $image;
    }
}
