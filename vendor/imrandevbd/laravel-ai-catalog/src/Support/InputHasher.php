<?php

namespace ImranDevBd\AiCatalog\Support;

class InputHasher
{
    public static function make(array $payload): string
    {
        $normalized = $payload;
        ksort($normalized);

        return 'ai-catalog.'.sha1((string) json_encode($normalized));
    }
}
