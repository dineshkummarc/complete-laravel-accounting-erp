<?php

namespace ImranDevBd\AiCatalog\Image;

use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiCatalog\Models\CatalogImageHash;
use ImranDevBd\AiCatalog\Support\ListingSchema;
use ImranDevBd\AiHub\Facades\AIHub;

class ImageIntelligence
{
    public function __construct(
        protected PerceptualHasher $hasher = new PerceptualHasher,
        protected ImageQualityChecker $quality = new ImageQualityChecker,
        protected ImageConverter $converter = new ImageConverter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(string $image, GenerationInput $input): array
    {
        $localPath = is_file($image) ? $image : null;
        $size = (int) config('ai-catalog.images.hash_size', 8);
        $hash = $this->hasher->hash($image, $size);
        $inspect = $this->quality->inspect($image);
        $duplicates = [];

        if (config('ai-catalog.images.duplicates', true)) {
            $duplicates = $this->findDuplicates($hash, $input->tenantId);
            try {
                CatalogImageHash::query()->create([
                    'tenant_id' => $input->tenantId,
                    'hash' => $hash,
                    'path' => $localPath,
                    'product_type' => $input->productType,
                    'product_id' => is_object($input->product) ? $input->product->getKey() : (is_scalar($input->product) ? $input->product : null),
                ]);
            } catch (\Throwable) {
                // table may not exist yet during early install
            }
        }

        $thumbs = [];
        if ($localPath && config('ai-catalog.images.thumbnails', true)) {
            $dir = storage_path('app/'.trim((string) config('ai-catalog.images.directory', 'ai-catalog'), '/').'/thumbs/'.substr($hash, 0, 12));
            $thumbs = $this->converter->thumbnails($localPath, (array) config('ai-catalog.images.thumbnail_widths', [150, 400]), $dir);
        }

        $alt = null;
        $ocr = null;
        if (config('ai-catalog.images.alt_text') || config('ai-catalog.images.ocr')) {
            [$alt, $ocr] = $this->visionCopy($image, $input);
        }

        $lifestyle = [];
        if (config('ai-catalog.images.lifestyle_prompts', true)) {
            $seed = $input->keyword ?: $input->promptSeed();
            $lifestyle = [
                'Lifestyle shot of '.$seed.' on a sunlit kitchen counter, editorial product photography, no text.',
                'Close-up detail of '.$seed.' in use, shallow depth of field, brand-safe, no logos invented.',
            ];
        }

        $flags = $inspect['flags'];
        if ($duplicates !== []) {
            $flags[] = 'duplicate_image';
        }

        return [
            'hash' => $hash,
            'quality' => $inspect,
            'thumbnails' => $thumbs,
            'duplicates' => $duplicates,
            'alt_text' => $alt,
            'ocr_text' => $ocr,
            'lifestyle_prompts' => $lifestyle,
            'flags' => $flags,
        ];
    }

    /**
     * @return array{0:?string,1:?string}
     */
    protected function visionCopy(string $image, GenerationInput $input): array
    {
        try {
            $request = AIHub::provider(config('ai-catalog.provider'))
                ->forJob('ai-catalog.image-vision')
                ->system('Describe the product photo for ecommerce accessibility and extract any visible label text.')
                ->prompt('Return JSON with alt_text (max 125 chars) and ocr_text (labels, barcodes, ingredients, or null).')
                ->image($this->asDataUri($image))
                ->asJsonSchema(ListingSchema::altText(), 'ImageCopy')
                ->recoverJson();

            if ($model = config('ai-catalog.vision_model')) {
                $request->model((string) $model);
            }

            $json = $request->generate()->json() ?? [];

            return [
                isset($json['alt_text']) ? (string) $json['alt_text'] : null,
                isset($json['ocr_text']) ? (string) $json['ocr_text'] : null,
            ];
        } catch (\Throwable) {
            return [null, null];
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function findDuplicates(string $hash, ?string $tenantId): array
    {
        try {
            $query = CatalogImageHash::query()->where('hash', $hash);
            if ($tenantId) {
                $query->where('tenant_id', $tenantId);
            }

            return $query->limit(5)->get(['id', 'path', 'product_id', 'generation_id'])->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    protected function asDataUri(string $image): string
    {
        if (str_starts_with($image, 'http') || str_starts_with($image, 'data:')) {
            return $image;
        }
        if (is_file($image)) {
            $mime = mime_content_type($image) ?: 'image/jpeg';

            return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($image));
        }

        return $image;
    }
}
