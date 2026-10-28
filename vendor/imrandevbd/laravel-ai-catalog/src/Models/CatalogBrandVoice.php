<?php

namespace ImranDevBd\AiCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CatalogBrandVoice extends Model
{
    protected $table = 'ai_catalog_brand_voices';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->slug) {
                $model->slug = Str::slug($model->name);
            }
        });
    }
}
