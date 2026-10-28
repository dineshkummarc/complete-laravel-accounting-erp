<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Jobs\ProcessBulkImportJob;
use ImranDevBd\AiCatalog\Models\CatalogBulkJob;
use ImranDevBd\AiCatalog\PendingGeneration;
use Illuminate\Support\Facades\File;

class BulkImporter
{
    public function __construct(
        protected ListingGenerator $generator = new ListingGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function queue(string $path, array $options = []): CatalogBulkJob
    {
        $rows = $this->parse($path);
        $job = CatalogBulkJob::query()->create([
            'filename' => basename($path),
            'status' => 'pending',
            'total' => count($rows),
            'processed' => 0,
            'failed_count' => 0,
            'options' => array_merge($options, ['rows' => $rows]),
        ]);

        ProcessBulkImportJob::dispatch($job->id)->onQueue((string) config('ai-catalog.queue', 'default'));

        return $job;
    }

    public function process(CatalogBulkJob $job): void
    {
        $job->status = 'processing';
        $job->save();

        $rows = $job->options['rows'] ?? [];
        $failed = 0;
        $processed = 0;

        foreach ($rows as $row) {
            try {
                $pending = $this->rowToPending(is_array($row) ? $row : []);
                $pending->generateAsync();
            } catch (\Throwable) {
                $failed++;
            }
            $processed++;
            $job->processed = $processed;
            $job->failed_count = $failed;
            $job->save();
        }

        $job->status = $failed === count($rows) && $rows !== [] ? 'failed' : 'completed';
        $job->save();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parse(string $path): array
    {
        if (! is_file($path)) {
            throw new \ImranDevBd\AiCatalog\Exceptions\AiCatalogException("Bulk file not found: {$path}");
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'json') {
            $decoded = json_decode(File::get($path), true);

            return array_values(is_array($decoded) ? $decoded : []);
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);

            return [];
        }

        $headers = array_map(fn ($h) => strtolower(trim((string) $h)), $headers);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $data[$i] ?? null;
            }
            $rows[] = $row;
        }
        fclose($handle);

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function rowToPending(array $row): PendingGeneration
    {
        $pending = new PendingGeneration($this->generator);

        $keyword = (string) ($row['keyword'] ?? $row['name'] ?? $row['title'] ?? $row['text'] ?? '');
        $image = $row['image'] ?? $row['image_url'] ?? null;
        $url = $row['url'] ?? $row['link'] ?? null;

        if (is_string($image) && $image !== '') {
            $pending->fromImage($image);
        } elseif (is_string($url) && $url !== '') {
            $pending->fromUrl($url);
        } else {
            $pending->fromKeyword($keyword !== '' ? $keyword : 'product');
        }

        if (! empty($row['locale'])) {
            $pending->locale((string) $row['locale']);
        }
        if (! empty($row['tone'])) {
            $pending->tone((string) $row['tone']);
        }
        if (! empty($row['brand_voice'])) {
            $pending->brandVoice((string) $row['brand_voice']);
        }
        if (isset($row['product_id']) && $row['product_id'] !== '') {
            $pending->forProduct($row['product_id']);
        }

        return $pending;
    }
}
