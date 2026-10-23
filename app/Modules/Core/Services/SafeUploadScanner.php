<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Modules\Core\Contracts\UploadScanner;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

final class SafeUploadScanner implements UploadScanner
{
    public function scan(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), ['application/pdf', 'image/png', 'image/jpeg', 'text/plain', 'text/csv'], true) || $file->getSize() > 10485760) {
            throw ValidationException::withMessages(['file' => 'Unsupported file type or file over 10 MB.']);
        }
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false || str_contains($contents, '<?php') || str_contains($contents, 'EICAR-STANDARD-ANTIVIRUS-TEST-FILE')) {
            throw ValidationException::withMessages(['file' => 'Upload failed security validation.']);
        }
        $scanner = config('accounting.upload_scanner');
        if (is_string($scanner) && $scanner !== '') {
            $process = new Process([$scanner, '--no-summary', $file->getRealPath()]);
            $process->setTimeout(60);
            $process->run();
            if (! $process->isSuccessful()) {
                throw ValidationException::withMessages(['file' => 'Antivirus scan did not pass.']);
            }
        }
    }
}
