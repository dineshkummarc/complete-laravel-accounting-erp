<?php

declare(strict_types=1);

namespace App\Integrations\Contracts;

interface PdfRenderer
{
    public function render(string $html, string $preset = 'universal'): string;
}
