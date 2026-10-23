<x-filament-panels::page>
{{ $this->changeAction }}
<div class="grid gap-4 md:grid-cols-3">@foreach($this->moduleCards() as $key => $card)
<x-filament::section><h2>{{ $card['name'] }}</h2><p>{{ $card['enabled'] ? __('business.enabled') : __('business.disabled') }}</p>
@if($card['dependencies'])<p>{{ __('business.dependencies') }}: {{ $card['dependencies'] }}</p>@endif</x-filament::section>
@endforeach</div>
</x-filament-panels::page>