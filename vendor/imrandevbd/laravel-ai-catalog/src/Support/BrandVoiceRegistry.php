<?php

namespace ImranDevBd\AiCatalog\Support;

class BrandVoiceRegistry
{
    public function resolve(?string $name): ?string
    {
        if ($name === null || $name === '') {
            return null;
        }

        $configured = (array) config('ai-catalog.brand_voices', []);
        if (isset($configured[$name]) && is_string($configured[$name])) {
            return $configured[$name];
        }

        try {
            $model = \ImranDevBd\AiCatalog\Models\CatalogBrandVoice::query()
                ->where('slug', $name)
                ->orWhere('name', $name)
                ->first();

            return $model?->instructions;
        } catch (\Throwable) {
            return null;
        }
    }
}
