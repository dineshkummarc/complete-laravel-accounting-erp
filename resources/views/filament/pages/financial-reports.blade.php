<x-filament-panels::page>
    <form wire:submit="generate" class="flex flex-wrap items-end gap-4">
        <label>{{ __('reports.title') }}<select wire:model="report" class="block rounded-lg border-gray-300 dark:bg-gray-900">@foreach($this->options() as $key => $title)<option value="{{ $key }}">{{ $title }}</option>@endforeach</select></label>
        <label>{{ __('reports.column_account') }}<select wire:model="accountId" class="block rounded-lg border-gray-300 dark:bg-gray-900"><option value="">{{ __('reports.all_accounts') }}</option>@foreach($this->accounts() as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
        <label>{{ __('reports.from') }}<input type="date" wire:model="from" class="block rounded-lg border-gray-300 dark:bg-gray-900"></label>
        <label>{{ __('reports.to') }}<input type="date" wire:model="to" class="block rounded-lg border-gray-300 dark:bg-gray-900"></label>
        <x-filament::button type="submit">{{ __('reports.run') }}</x-filament::button>
        <x-filament::button wire:click="download" color="gray">CSV</x-filament::button>
    </form>
    @foreach($errors->all() as $message)<p class="text-danger-600">{{ $message }}</p>@endforeach
    @if($result)
        @if($result['metadata']['truncated'])<p>{{ __('reports.preview') }}</p>@endif
        @if($result['metadata']['cash_flow_basis'] ?? null)<p>{{ $result['metadata']['cash_flow_basis'] }}</p>@endif
        <div class="overflow-x-auto rounded-xl bg-white shadow-sm dark:bg-gray-900"><table class="w-full text-sm"><thead><tr>@foreach($result['columns'] as $title)<th class="p-3 text-start">{{ $title }}</th>@endforeach</tr></thead><tbody>@foreach($result['rows'] as $row)<tr class="border-t">@foreach($result['columns'] as $key => $title)<td class="p-3">{{ $row[$key] ?? '' }}</td>@endforeach</tr>@endforeach@if(empty($result['rows']))<tr><td class="p-4" colspan="{{ count($result['columns']) }}">{{ __('reports.no_activity') }}</td></tr>@endif</tbody></table></div>
        @foreach($result['totals'] as $key => $value)<p>{{ __('reports.column_'.$key) }}: {{ $value }}</p>@endforeach
    @endif
</x-filament-panels::page>
