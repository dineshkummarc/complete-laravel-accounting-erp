<?php

declare(strict_types=1);

namespace App\Integrations\Pdf;

use App\Integrations\Contracts\PdfRenderer;
use ImranDev\UnicodePdf\Facades\UnicodePdf;

final class PackagePdfRenderer implements PdfRenderer
{
    public function render(string $html, string $preset = 'universal'): string
    {
        return UnicodePdf::preset($preset)->loadHtml($html)->output();
    }
}
