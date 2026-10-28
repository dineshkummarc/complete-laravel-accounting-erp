<?php

namespace ImranDevBd\AiCatalog\Services;

use Illuminate\Support\Facades\Http;

class UrlReader
{
    public function fetch(string $url): string
    {
        $response = Http::timeout(20)
            ->withHeaders(['User-Agent' => 'LaravelAiCatalog/1.0'])
            ->get($url);

        if (! $response->successful()) {
            return '';
        }

        $html = $response->body();
        $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);

        return mb_substr($text, 0, 8000);
    }
}
