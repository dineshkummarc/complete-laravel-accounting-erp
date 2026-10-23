<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

interface AiGateway
{
    /** @param array<string, mixed> $schema
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public function suggest(string $job, string $prompt, array $schema, array $rules): array;
}
