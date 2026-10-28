<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GenerationInput;
use Illuminate\Support\Str;

class TaxonomyMatcher
{
    /**
     * Load taxonomy rows for a generation (never invents entries).
     *
     * @return array<int, array{id: int|string, name: string, parent_id?: int|string|null}>
     */
    public function load(?array $override = null): array
    {
        if (is_array($override) && $override !== []) {
            return $this->normalize($override);
        }

        $source = (string) config('ai-catalog.taxonomy.source', 'model');

        if ($source === 'config') {
            return $this->normalize((array) config('ai-catalog.taxonomy.items', []));
        }

        $modelClass = config('ai-catalog.taxonomy.model');
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return $this->normalize((array) config('ai-catalog.taxonomy.items', []));
        }

        $name = (string) config('ai-catalog.taxonomy.name_column', 'name');
        $id = (string) config('ai-catalog.taxonomy.id_column', 'id');
        $parent = (string) config('ai-catalog.taxonomy.parent_column', 'parent_id');

        try {
            $rows = $modelClass::query()->get([$id, $name, $parent]);
        } catch (\Throwable) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'id' => $row->{$id},
                'name' => (string) $row->{$name},
                'parent_id' => $row->{$parent} ?? null,
            ];
        }

        return $items;
    }

    /**
     * @param  array<int, array{id: int|string, name: string, parent_id?: int|string|null}>  $taxonomy
     * @return array{id: int|string, name: string, parent_id?: int|string|null, score: float}|null
     */
    public function match(?string $suggested, array $taxonomy): ?array
    {
        if ($suggested === null || trim($suggested) === '' || $taxonomy === []) {
            return null;
        }

        $needle = $this->normalizeName($suggested);
        $min = (float) config('ai-catalog.taxonomy.min_score', 55);
        $best = null;
        $bestScore = 0.0;

        foreach ($taxonomy as $row) {
            $name = $this->normalizeName((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            if ($name === $needle) {
                return array_merge($row, ['score' => 100.0]);
            }

            similar_text($needle, $name, $percent);
            $contains = str_contains($name, $needle) || str_contains($needle, $name);
            $score = $contains ? max($percent, 80.0) : $percent;

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $row;
            }
        }

        if ($best === null || $bestScore < $min) {
            return null;
        }

        return array_merge($best, ['score' => round($bestScore, 2)]);
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, array{id: int|string, name: string, parent_id?: int|string|null}>
     */
    public function normalize(array $items): array
    {
        $out = [];

        foreach ($items as $key => $item) {
            if (is_string($item)) {
                $out[] = ['id' => $key, 'name' => $item, 'parent_id' => null];
                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $name = (string) ($item['name'] ?? $item['title'] ?? '');
            if ($name === '') {
                continue;
            }

            $out[] = [
                'id' => $item['id'] ?? $key,
                'name' => $name,
                'parent_id' => $item['parent_id'] ?? $item['parentId'] ?? null,
            ];
        }

        return $out;
    }

    protected function normalizeName(string $value): string
    {
        return Str::lower(trim($value));
    }
}
