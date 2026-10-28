<?php

namespace ImranDevBd\AiCatalog\Services;

use ImranDevBd\AiCatalog\Data\GeneratedListing;
use ImranDevBd\AiCatalog\Data\GenerationInput;
use ImranDevBd\AiCatalog\Exceptions\AiCatalogException;
use Illuminate\Database\Eloquent\Model;

class SchemaMapper
{
    /**
     * Map a generated listing onto host-app product attributes.
     *
     * @return array<string, mixed>
     */
    public function attributes(GeneratedListing $listing, GenerationInput $input): array
    {
        $map = (array) config('ai-catalog.schema.map', []);
        $jsonColumns = (array) config('ai-catalog.schema.json_columns', []);

        $source = [
            'title' => $listing->title,
            'meta_title' => $listing->metaTitle,
            'meta_description' => $listing->metaDescription,
            'short_description' => $listing->shortDescription,
            'long_description' => $listing->longDescription,
            'specs' => $listing->specs,
            'tags' => $listing->tags,
            'category' => $listing->categoryId,
            'subcategory' => $listing->subcategoryId,
            'locale' => $input->locale,
            'faq' => $listing->faq,
            'care_instructions' => $listing->careInstructions,
            'size_guide' => $listing->sizeGuide,
            'gtin' => $listing->gtin,
            'mpn' => $listing->mpn,
            'sku' => $listing->sku,
            'json_ld' => $listing->jsonLd,
            'alt_text' => $listing->altText,
            'translations' => $listing->translations,
        ];

        $attributes = [];
        foreach ($map as $from => $to) {
            if (! is_string($to) || $to === '' || ! array_key_exists($from, $source)) {
                continue;
            }

            $value = $source[$from];
            if (in_array($to, $jsonColumns, true) && ! is_string($value)) {
                $attributes[$to] = $value;
                continue;
            }

            $attributes[$to] = $value;
        }

        return $attributes;
    }

    public function apply(GeneratedListing $listing, GenerationInput $input, ?Model $product = null): Model
    {
        $attributes = $this->attributes($listing, $input);
        $modelClass = (string) config('ai-catalog.schema.model');
        $mode = (string) config('ai-catalog.schema.on_approve', 'update_or_create');

        $product ??= $this->resolveProduct($input);

        if ($product instanceof Model) {
            $product->fill($attributes);
            $product->save();

            return $product;
        }

        if (! class_exists($modelClass)) {
            throw new AiCatalogException("Product model [{$modelClass}] is not configured or does not exist.");
        }

        /** @var class-string<Model> $modelClass */
        if ($mode === 'create' || $product === null) {
            return $modelClass::query()->create($attributes);
        }

        return $modelClass::query()->create($attributes);
    }

    public function resolveProduct(GenerationInput $input): ?Model
    {
        if ($input->product instanceof Model) {
            return $input->product;
        }

        if ($input->product === null) {
            return null;
        }

        $modelClass = $input->productType ?: (string) config('ai-catalog.schema.model');
        if (! class_exists($modelClass)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        return $modelClass::query()->find($input->product);
    }

    public function modelClass(): string
    {
        return (string) config('ai-catalog.schema.model');
    }
}
