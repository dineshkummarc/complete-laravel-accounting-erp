<?php

namespace ImranDevBd\AiCatalog\Image;

class ImageQualityChecker
{
    /**
     * @return array{width:int,height:int,aspect:float,bytes:int,mime:?string,flags:list<string>,blur_score:?float}
     */
    public function inspect(string $pathOrBinary): array
    {
        $binary = $this->binary($pathOrBinary);
        $info = @getimagesizefromstring($binary);
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        $mime = is_array($info) ? ($info['mime'] ?? null) : null;
        $aspect = $height > 0 ? round($width / $height, 4) : 0.0;
        $flags = [];

        $minW = (int) config('ai-catalog.images.min_width', 500);
        $minH = (int) config('ai-catalog.images.min_height', 500);
        $maxAspect = (float) config('ai-catalog.images.max_aspect', 2.2);
        $minAspect = (float) config('ai-catalog.images.min_aspect', 0.45);

        if ($width > 0 && $width < $minW) {
            $flags[] = 'image_too_narrow';
        }
        if ($height > 0 && $height < $minH) {
            $flags[] = 'image_too_short';
        }
        if ($aspect > $maxAspect) {
            $flags[] = 'image_aspect_too_wide';
        }
        if ($aspect > 0 && $aspect < $minAspect) {
            $flags[] = 'image_aspect_too_tall';
        }
        if (strlen($binary) < 8_000) {
            $flags[] = 'image_file_tiny';
        }

        $blur = $this->blurScore($binary);
        if ($blur !== null && $blur < 12) {
            $flags[] = 'image_blurry';
        }

        $lower = strtolower($binary);
        if (str_contains($lower, 'watermark') || str_contains($lower, 'getty') || str_contains($lower, 'shutterstock')) {
            $flags[] = 'image_possible_watermark';
        }

        return [
            'width' => $width,
            'height' => $height,
            'aspect' => $aspect,
            'bytes' => strlen($binary),
            'mime' => $mime,
            'flags' => $flags,
            'blur_score' => $blur,
        ];
    }

    protected function blurScore(string $binary): ?float
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return null;
        }

        $w = imagesx($image);
        $h = imagesy($image);
        $sampleW = min(64, $w);
        $sampleH = min(64, $h);
        $thumb = imagecreatetruecolor($sampleW, $sampleH);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $sampleW, $sampleH, $w, $h);
        imagedestroy($image);

        $variance = 0.0;
        $prev = null;
        for ($y = 0; $y < $sampleH; $y++) {
            for ($x = 0; $x < $sampleW; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $gray = ((($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF)) / 3;
                if ($prev !== null) {
                    $d = $gray - $prev;
                    $variance += $d * $d;
                }
                $prev = $gray;
            }
        }
        imagedestroy($thumb);

        $n = max(($sampleW * $sampleH) - 1, 1);

        return round($variance / $n, 2);
    }

    protected function binary(string $pathOrBinary): string
    {
        if (is_file($pathOrBinary)) {
            return (string) file_get_contents($pathOrBinary);
        }

        if (str_starts_with($pathOrBinary, 'data:')) {
            return (string) base64_decode((string) substr($pathOrBinary, (int) strpos($pathOrBinary, ',') + 1), true);
        }

        if (str_starts_with($pathOrBinary, 'http')) {
            try {
                return (string) \Illuminate\Support\Facades\Http::timeout(15)->get($pathOrBinary)->body();
            } catch (\Throwable) {
                return '';
            }
        }

        return $pathOrBinary;
    }
}
