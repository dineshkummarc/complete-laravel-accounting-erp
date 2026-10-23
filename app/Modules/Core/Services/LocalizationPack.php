<?php

declare(strict_types=1);

namespace App\Modules\Core\Services;

use InvalidArgumentException;

final class LocalizationPack
{
    /** @return array<string, mixed> */
    public function defaults(string $key): array
    {
        $pack = config('localization-packs.'.$key);
        if (! is_array($pack)) {
            throw new InvalidArgumentException('Unknown localization pack.');
        }

        return $pack;
    }

    public function numerals(string $value, string $locale): string
    {
        return match ($locale) {
            'bn' => strtr($value, array_combine(range('0', '9'), ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'])), 'ar' => strtr($value, array_combine(range('0', '9'), ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'])), default => $value
        };
    }
}
