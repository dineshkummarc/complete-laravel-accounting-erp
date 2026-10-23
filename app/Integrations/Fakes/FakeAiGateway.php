<?php

declare(strict_types=1);

namespace App\Integrations\Fakes;

use App\Integrations\Contracts\AiGateway;
use Illuminate\Support\Facades\Validator;

final class FakeAiGateway implements AiGateway
{
    /** @param array<string, mixed> $response */
    public function __construct(public array $response = []) {}

    public function suggest(string $job, string $prompt, array $schema, array $rules): array
    {
        return Validator::make($this->response, $rules)->validate();
    }
}
