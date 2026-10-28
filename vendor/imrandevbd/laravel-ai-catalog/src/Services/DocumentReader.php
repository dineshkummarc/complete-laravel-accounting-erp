<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Exceptions\AiCatalogException;

class DocumentReader
{
    public function read(string $path): string
    {
        if (! is_file($path)) {
            throw new AiCatalogException("Document not found: {$path}");
        }

        $ext = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $raw = (string) file_get_contents($path);

        if (in_array($ext, ['html', 'htm'], true)) {
            $raw = html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $raw = preg_replace('/\s+/', ' ', $raw) ?? $raw;

        return trim(mb_substr($raw, 0, 12000));
    }
}
