<?php

namespace ImranDevBd\AiCatalog\Image;

class ImageConverter
{
    /**
     * @param  list<int>  $widths
     * @return list<array{width:int,path:string,format:string}>
     */
    public function thumbnails(string $path, array $widths, string $directory): array
    {
        if (! function_exists('imagecreatefromstring') || ! is_file($path)) {
            return [];
        }

        $binary = (string) file_get_contents($path);
        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return [];
        }

        $srcW = imagesx($source);
        $srcH = imagesy($source);
        $out = [];

        if (! is_dir($directory)) {
            @mkdir($directory, 0777, true);
        }

        foreach ($widths as $width) {
            $width = (int) $width;
            if ($width < 16) {
                continue;
            }
            $height = (int) max(1, round($srcH * ($width / max($srcW, 1))));
            $thumb = imagecreatetruecolor($width, $height);
            imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $srcW, $srcH);
            $file = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'thumb-'.$width.'.jpg';
            imagejpeg($thumb, $file, 82);
            imagedestroy($thumb);
            $out[] = ['width' => $width, 'path' => $file, 'format' => 'jpeg'];

            if (config('ai-catalog.images.webp') && function_exists('imagewebp')) {
                $webp = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'thumb-'.$width.'.webp';
                $thumb = imagecreatetruecolor($width, $height);
                imagecopyresampled($thumb, $source, 0, 0, 0, 0, $width, $height, $srcW, $srcH);
                imagewebp($thumb, $webp, 80);
                imagedestroy($thumb);
                $out[] = ['width' => $width, 'path' => $webp, 'format' => 'webp'];
            }
        }

        imagedestroy($source);

        return $out;
    }
}
