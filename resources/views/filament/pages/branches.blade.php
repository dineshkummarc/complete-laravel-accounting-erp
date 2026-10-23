<x-filament-panels::page>
<div class="flex gap-4">{{ $this->switchAction }} {{ $this->createAction }} {{ $this->importAction }} {{ $this->attachmentAction }}
<a href="{{ route('business.branches.export') }}">{{ __('business.export_csv') }}</a></div>
@foreach($this->branchList() as $branch)<x-filament::section><h2>{{ $branch['name'] }}</h2><p>{{ $branch['code'] }}</p></x-filament::section>@endforeach
@foreach($this->attachmentLinks() as $url => $name)<a href="{{ $url }}">{{ $name }}</a>@endforeach
</x-filament-panels::page>