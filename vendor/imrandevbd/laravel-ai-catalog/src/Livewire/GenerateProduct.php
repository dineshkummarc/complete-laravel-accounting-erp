<?php

namespace ImranDevBd\AiCatalog\Livewire;

use ImranDevBd\AiCatalog\Facades\AiCatalog;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use Livewire\Component;

class GenerateProduct extends Component
{
    public ?int $productId = null;

    public string $keyword = '';

    public string $tone = 'professional';

    public string $locale = 'en';

    public ?int $generationId = null;

    public ?string $error = null;

    public function mount(?int $productId = null): void
    {
        $this->productId = $productId;
        $this->tone = (string) config('ai-catalog.tone', 'professional');
        $this->locale = (string) config('ai-catalog.locale', 'en');
    }

    public function generate(): void
    {
        $this->validate([
            'keyword' => ['required', 'string', 'max:500'],
        ]);

        $this->error = null;

        try {
            $pending = AiCatalog::fromKeyword($this->keyword)
                ->tone($this->tone)
                ->locale($this->locale);

            if ($this->productId) {
                $pending->forProduct($this->productId);
            }

            $generation = $pending->generate();
            $this->generationId = $generation->id;
            $this->dispatch('ai-catalog-generated', id: $generation->id);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function approve(): void
    {
        if (! $this->generationId) {
            return;
        }

        AiCatalog::approve($this->generationId);
        $this->dispatch('ai-catalog-approved', id: $this->generationId);
    }

    public function render()
    {
        $generation = $this->generationId
            ? CatalogGeneration::query()->find($this->generationId)
            : null;

        return view('ai-catalog::livewire.generate-product', [
            'generation' => $generation,
            'tones' => (array) config('ai-catalog.tones', []),
            'locales' => (array) config('ai-catalog.locales', ['en']),
        ]);
    }
}
