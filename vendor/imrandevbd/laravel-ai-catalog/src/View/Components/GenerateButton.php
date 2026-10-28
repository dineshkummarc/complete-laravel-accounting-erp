<?php

namespace ImranDevBd\AiCatalog\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GenerateButton extends Component
{
    public function __construct(
        public mixed $product = null,
        public string $keyword = '',
        public string $label = 'Generate with AI',
    ) {}

    public function render(): View
    {
        return view('ai-catalog::components.generate-button');
    }
}
