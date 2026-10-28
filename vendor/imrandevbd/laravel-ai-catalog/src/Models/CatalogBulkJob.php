<?php

namespace ImranDevBd\AiCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CatalogBulkJob extends Model
{
    protected $table = 'ai_catalog_bulk_jobs';

    protected $guarded = [];

    protected $casts = [
        'options' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (! $model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function progressPercent(): int
    {
        if ($this->total < 1) {
            return 0;
        }

        return (int) floor(($this->processed / $this->total) * 100);
    }
}
