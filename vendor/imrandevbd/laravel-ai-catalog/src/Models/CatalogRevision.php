<?php

namespace ImranDevBd\AiCatalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogRevision extends Model
{
    public $timestamps = false;

    protected $table = 'ai_catalog_revisions';

    protected $guarded = [];

    protected $casts = [
        'before' => 'array',
        'after' => 'array',
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function generation(): BelongsTo
    {
        return $this->belongsTo(CatalogGeneration::class, 'generation_id');
    }
}
