<?php

declare(strict_types=1);

namespace App\Integrations\Ai;

use App\Integrations\Contracts\AiGateway;
use Illuminate\Support\Facades\Validator;
use ImranDevBd\AiHub\Facades\AIHub;
use RuntimeException;

final class PackageAiGateway implements AiGateway
{
    public function suggest(string $job, string $prompt, array $schema, array $rules): array
    {
        if (! str_starts_with($job, 'accounting.') && ! str_starts_with($job, 'catalog.')) {
            throw new RuntimeException('Unrecognized AI job namespace.');
        }
        $redacted = preg_replace('/[\w.+-]+@[\w.-]+\.[a-z]{2,}|\+?\d[\d\s().-]{6,}\d/i', '[REDACTED]', $prompt) ?? '';
        $response = AIHub::structured($schema)->forJob($job)->prompt($redacted)->generate();
        $data = $response->json();
        if (! $response->success || ! is_array($data)) {
            throw new RuntimeException('AI did not return a valid suggestion.');
        }

        return Validator::make($data, $rules)->validate();
    }
}
