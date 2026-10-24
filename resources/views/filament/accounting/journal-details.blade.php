<div class="space-y-4"><h2>{{ $entry->number ?? __('accounting.status_draft') }}</h2><p>{{ $entry->narration }}</p>
<table class="w-full text-sm"><thead><tr><th>{{ __('accounting.account') }}</th><th>{{ __('accounting.debit') }}</th><th>{{ __('accounting.credit') }}</th></tr></thead><tbody>
@foreach($entry->lines as $line)<tr><td>{{ $line->account->code }} · {{ $line->account->name }}</td><td>{{ $line->base_debit }}</td><td>{{ $line->base_credit }}</td></tr>@endforeach
</tbody></table>
@foreach($entry->attachments as $attachment)<p><a class="text-primary-600 underline" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('business.attachment', now()->addMinutes(10), ['uuid' => $attachment->uuid]) }}">{{ $attachment->original_name }}</a></p>@endforeach
</div>