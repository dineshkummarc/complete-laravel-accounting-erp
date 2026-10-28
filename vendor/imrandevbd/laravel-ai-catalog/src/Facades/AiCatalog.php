<?php

namespace ImranDevBd\AiCatalog\Facades;

use Illuminate\Support\Facades\Facade;
use ImranDevBd\AiCatalog\CatalogManager;

/**
 * @method static \ImranDevBd\AiCatalog\PendingGeneration pending()
 * @method static \ImranDevBd\AiCatalog\PendingGeneration fromKeyword(string $keyword)
 * @method static \ImranDevBd\AiCatalog\PendingGeneration fromText(string $text)
 * @method static \ImranDevBd\AiCatalog\PendingGeneration fromImage(string $pathOrUrl)
 * @method static \ImranDevBd\AiCatalog\PendingGeneration fromUrl(string $url)
 * @method static \ImranDevBd\AiCatalog\PendingGeneration fromVoice(string $audioPath)
 * @method static \ImranDevBd\AiCatalog\PendingGeneration fromDocument(string $path)
 * @method static \ImranDevBd\AiCatalog\PendingGeneration forProduct(\Illuminate\Database\Eloquent\Model|int|string $product, ?string $type = null)
 * @method static \ImranDevBd\AiCatalog\Models\CatalogGeneration generate(\ImranDevBd\AiCatalog\PendingGeneration|string $input)
 * @method static \ImranDevBd\AiCatalog\Models\CatalogGeneration regenerateField(\ImranDevBd\AiCatalog\Models\CatalogGeneration|int $generation, string $field, ?int $actorId = null)
 * @method static \ImranDevBd\AiCatalog\Models\CatalogGeneration approve(\ImranDevBd\AiCatalog\Models\CatalogGeneration|int $generation, ?int $actorId = null)
 * @method static \ImranDevBd\AiCatalog\Models\CatalogGeneration reject(\ImranDevBd\AiCatalog\Models\CatalogGeneration|int $generation, ?string $reason = null, ?int $actorId = null)
 * @method static \ImranDevBd\AiCatalog\Models\CatalogGeneration review(\ImranDevBd\AiCatalog\Models\CatalogGeneration|int $generation, ?int $actorId = null)
 * @method static \ImranDevBd\AiCatalog\Models\CatalogGeneration edit(\ImranDevBd\AiCatalog\Models\CatalogGeneration|int $generation, array $payload, ?int $actorId = null)
 * @method static \ImranDevBd\AiCatalog\Services\ListingGenerator generator()
 * @method static \ImranDevBd\AiCatalog\Services\WorkflowService workflow()
 * @method static array costs(?string $tenantId = null, int $days = 30)
 * @method static array googleFeed(?int $limit = 100)
 * @method static array agenticFeed(?int $limit = 100)
 * @method static string llmsTxt(?int $limit = 100, bool $full = false)
 * @method static array dppFeed(?int $limit = 100)
 * @method static array audit(?string $tenantId = null, int $staleDays = 90)
 *
 * @see \ImranDevBd\AiCatalog\CatalogManager
 */
class AiCatalog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CatalogManager::class;
    }
}
