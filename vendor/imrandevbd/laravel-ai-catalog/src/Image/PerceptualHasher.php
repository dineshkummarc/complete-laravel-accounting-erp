<?php

namespace ImranDevBd\AiCatalog\Image;

class PerceptualHasher
{
    public function hash(string $pathOrBinary, int $size = 8): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return hash('sha1', is_file($pathOrBinary) ? (string) file_get_contents($pathOrBinary) : $pathOrBinary);
        }

        $image = $this->load($pathOrBinary);
        if ($image === false) {
            return hash('sha1', is_file($pathOrBinary) ? (string) file_get_contents($pathOrBinary) : $pathOrBinary);
        }

        $thumb = imagecreatetruecolor($size, $size);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $size, $size, imagesx($image), imagesy($image));
        imagedestroy($image);

        $pixels = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $rgb = imagecolorat($thumb, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $pixels[] = (int) (($r * 30 + $g * 59 + $b * 11) / 100);
            }
        }
        imagedestroy($thumb);

        $avg = array_sum($pixels) / max(count($pixels), 1);
        $bits = '';
        foreach ($pixels as $value) {
            $bits .= $value >= $avg ? '1' : '0';
        }

        return $bits;
    }

    public function distance(string $a, string $b): int
    {
        $len = min(strlen($a), strlen($b));
        if ($len === 0) {
            return 64;
        }

        $diff = 0;
        for ($i = 0; $i < $len; $i++) {
            if ($a[$i] !== $b[$i]) {
                $diff++;
            }
        }

        return $diff;
    }

    /**
     * @return \GdImage|false
     */
    protected function load(string $pathOrBinary)
    {
        $binary = is_file($pathOrBinary) ? (string) file_get_contents($pathOrBinary) : $pathOrBinary;
        if (str_starts_with($binary, 'data:')) {
            $binary = (string) base64_decode((string) substr($binary, (int) strpos($binary, ',') + 1), true);
        }

        return @imagecreatefromstring($binary);
    }
}
