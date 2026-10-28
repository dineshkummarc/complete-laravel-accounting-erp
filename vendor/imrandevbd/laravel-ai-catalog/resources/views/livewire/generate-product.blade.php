@props([
    'generation' => null,
    'tones' => [],
    'locales' => [],
])

<div class="ai-catalog-generate space-y-4">
    @if ($error)
        <p class="text-sm text-red-600">{{ $error }}</p>
    @endif

    <div class="grid gap-3">
        <label class="block">
            <span class="text-sm font-medium">Keyword</span>
            <input type="text" wire:model="keyword" class="mt-1 w-full rounded border px-3 py-2" placeholder="wireless earbuds">
        </label>

        <div class="flex gap-3">
            <label class="block flex-1">
                <span class="text-sm font-medium">Tone</span>
                <select wire:model="tone" class="mt-1 w-full rounded border px-3 py-2">
                    @foreach ($tones as $option)
                        <option value="{{ $option }}">{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block flex-1">
                <span class="text-sm font-medium">Locale</span>
                <select wire:model="locale" class="mt-1 w-full rounded border px-3 py-2">
                    @foreach ($locales as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <button type="button" wire:click="generate" wire:loading.attr="disabled" class="rounded bg-slate-900 px-4 py-2 text-white">
            <span wire:loading.remove>Generate with AI</span>
            <span wire:loading>Generating…</span>
        </button>
    </div>

    @if ($generation && $generation->payload)
        <article class="rounded border p-4 space-y-2">
            <p class="text-xs uppercase tracking-wide text-slate-500">{{ $generation->status?->value }} · confidence {{ $generation->overall_confidence }}</p>
            <h3 class="text-lg font-semibold">{{ $generation->listing()->title }}</h3>
            <p>{{ $generation->listing()->shortDescription }}</p>
            <p class="text-sm text-slate-600">{{ $generation->listing()->metaDescription }}</p>
            @if ($generation->status?->canApprove())
                <button type="button" wire:click="approve" class="rounded bg-emerald-700 px-3 py-1.5 text-sm text-white">Approve</button>
            @endif
        </article>
    @endif
</div>
