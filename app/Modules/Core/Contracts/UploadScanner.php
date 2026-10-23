<?php

declare(strict_types=1);

namespace App\Modules\Core\Contracts;

use Illuminate\Http\UploadedFile;

interface UploadScanner
{
    public function scan(UploadedFile $file): void;
}
