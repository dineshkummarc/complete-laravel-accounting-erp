<?php

declare(strict_types=1);

namespace App\Integrations\Fakes;

use App\Integrations\Contracts\PdfRenderer;

final class FakePdfRenderer implements PdfRenderer
{
    public function __construct(public string $output = '%PDF-1.4 test') {}

    public function render(string $html, string $preset = 'universal'): string
    {
        return $this->output;
    }
}
