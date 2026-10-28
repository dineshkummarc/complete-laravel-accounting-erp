<?php

namespace ImranDevBd\AiCatalog\Http\Controllers;

use ImranDevBd\AiCatalog\Facades\AiCatalog;
use ImranDevBd\AiCatalog\Models\CatalogGeneration;
use ImranDevBd\AiCatalog\Services\BulkImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;

class CatalogApiController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        if ($tooMany = $this->rateLimit($request)) {
            return $tooMany;
        }

        $data = $request->validate([
            'keyword' => ['nullable', 'string', 'max:500'],
            'text' => ['nullable', 'string', 'max:20000'],
            'image' => ['nullable', 'string', 'max:4000'],
            'url' => ['nullable', 'url', 'max:2000'],
            'audio' => ['nullable', 'string', 'max:4000'],
            'document' => ['nullable', 'string', 'max:4000'],
            'locale' => ['nullable', 'string', 'max:12'],
            'locales' => ['nullable', 'array'],
            'tone' => ['nullable', 'string', 'max:32'],
            'brand_voice' => ['nullable', 'string', 'max:120'],
            'campaign' => ['nullable', 'string', 'max:80'],
            'region' => ['nullable', 'string', 'max:32'],
            'variants' => ['nullable', 'integer', 'min:0', 'max:3'],
            'product_id' => ['nullable'],
            'async' => ['nullable', 'boolean'],
        ]);

        $pending = AiCatalog::pending();

        if (! empty($data['image'])) {
            $pending->fromImage($data['image']);
        } elseif (! empty($data['url'])) {
            $pending->fromUrl($data['url']);
        } elseif (! empty($data['audio'])) {
            $pending->fromVoice($data['audio']);
        } elseif (! empty($data['document'])) {
            $pending->fromDocument($data['document']);
        } elseif (! empty($data['text'])) {
            $pending->fromText($data['text']);
        } elseif (! empty($data['keyword'])) {
            $pending->fromKeyword($data['keyword']);
        } else {
            return response()->json(['message' => 'Provide keyword, text, image, url, audio, or document.'], 422);
        }

        if (! empty($data['locale'])) {
            $pending->locale($data['locale']);
        }
        if (! empty($data['tone'])) {
            $pending->tone($data['tone']);
        }
        if (! empty($data['brand_voice'])) {
            $pending->brandVoice($data['brand_voice']);
        }
        if (! empty($data['locales'])) {
            $pending->locales($data['locales']);
        }
        if (! empty($data['campaign'])) {
            $pending->campaign($data['campaign']);
        }
        if (! empty($data['region'])) {
            $pending->region($data['region']);
        }
        if (isset($data['variants'])) {
            $pending->variants((int) $data['variants']);
        }
        if (isset($data['product_id'])) {
            $pending->forProduct($data['product_id']);
        }

        $generation = ! empty($data['async'])
            ? $pending->generateAsync()
            : $pending->generate();

        return response()->json($this->serialize($generation), 201);
    }

    public function show(string $uuid): JsonResponse
    {
        return response()->json($this->serialize($this->find($uuid)));
    }

    public function review(string $uuid): JsonResponse
    {
        return response()->json($this->serialize(AiCatalog::review($this->find($uuid))));
    }

    public function approve(string $uuid): JsonResponse
    {
        return response()->json($this->serialize(AiCatalog::approve($this->find($uuid))));
    }

    public function reject(Request $request, string $uuid): JsonResponse
    {
        $reason = $request->string('reason')->toString() ?: null;

        return response()->json($this->serialize(AiCatalog::reject($this->find($uuid), $reason)));
    }

    public function regenerate(string $uuid, string $field): JsonResponse
    {
        return response()->json($this->serialize(AiCatalog::regenerateField($this->find($uuid), $field)));
    }

    public function bulk(Request $request, BulkImporter $importer): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file'],
        ]);

        $uploaded = $request->file('file');
        $path = $uploaded->store('ai-catalog-bulk');
        $full = storage_path('app/'.$path);
        $job = $importer->queue($full, ['original' => $uploaded->getClientOriginalName()]);

        return response()->json([
            'id' => $job->id,
            'uuid' => $job->uuid,
            'status' => $job->status,
            'total' => $job->total,
        ], 202);
    }

    public function costs(): JsonResponse
    {
        return response()->json(AiCatalog::costs());
    }

    public function feed(): JsonResponse
    {
        return response()->json(['items' => AiCatalog::googleFeed()]);
    }

    public function agenticFeed(): JsonResponse
    {
        return response()->json(['items' => AiCatalog::agenticFeed()]);
    }

    public function dppFeed(): JsonResponse
    {
        return response()->json(['items' => AiCatalog::dppFeed()]);
    }

    public function audit(): JsonResponse
    {
        return response()->json(AiCatalog::audit());
    }

    public function bulkStatus(string $uuid): JsonResponse
    {
        $job = \ImranDevBd\AiCatalog\Models\CatalogBulkJob::query()->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'uuid' => $job->uuid,
            'status' => $job->status,
            'total' => $job->total,
            'processed' => $job->processed,
            'failed_count' => $job->failed_count,
            'progress' => $job->progressPercent(),
        ]);
    }

    protected function find(string $uuid): CatalogGeneration
    {
        return CatalogGeneration::query()->where('uuid', $uuid)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    protected function serialize(CatalogGeneration $generation): array
    {
        return [
            'id' => $generation->id,
            'uuid' => $generation->uuid,
            'status' => $generation->status?->value,
            'locale' => $generation->locale,
            'tone' => $generation->tone?->value,
            'payload' => $generation->payload,
            'mapped_payload' => $generation->mapped_payload,
            'confidence' => $generation->confidence,
            'overall_confidence' => $generation->overall_confidence,
            'flags' => $generation->flags,
            'variants' => $generation->variants,
            'cost_usd' => $generation->cost_usd,
            'provider' => $generation->provider,
            'model' => $generation->model,
            'error' => $generation->error,
        ];
    }

    protected function rateLimit(Request $request): ?JsonResponse
    {
        $limit = (string) config('ai-catalog.api.rate_limit', '30,1');
        [$max, $decay] = array_pad(explode(',', $limit), 2, '1');
        $key = 'ai-catalog:'.($request->user()?->getAuthIdentifier() ?: $request->ip());

        if (RateLimiter::tooManyAttempts($key, (int) $max)) {
            return response()->json(['message' => 'Too many generation requests.'], 429);
        }

        RateLimiter::hit($key, (int) $decay * 60);

        return null;
    }
}
