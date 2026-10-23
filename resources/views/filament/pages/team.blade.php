<x-filament-panels::page>{{ $this->memberAction }}
@foreach($this->members() as $member)<x-filament::section><h2>{{ $member['name'] }}</h2><p>{{ $member['email'] }} · {{ $member['is_owner'] ? __('business.owner') : __('business.staff') }}</p></x-filament::section>@endforeach
</x-filament-panels::page>