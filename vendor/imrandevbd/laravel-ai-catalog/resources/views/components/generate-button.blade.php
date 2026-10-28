<form method="POST" action="{{ url(trim(config('ai-catalog.api.prefix', 'api/ai-catalog'), '/').'/generate') }}" class="inline">
    @csrf
    @if ($keyword)
        <input type="hidden" name="keyword" value="{{ $keyword }}">
    @endif
    @if ($product)
        <input type="hidden" name="product_id" value="{{ is_object($product) ? $product->getKey() : $product }}">
    @endif
    <button type="submit" {{ $attributes->merge(['class' => 'ai-catalog-generate-button']) }}>
        {{ $label }}
    </button>
</form>
