@if(auth()->check() && app(\App\Modules\Core\Services\BusinessContext::class)->companyId)
<form method="POST" action="{{ route('business.branch') }}">@csrf
<label for="branch-switcher" class="sr-only">{{ __('business.branch') }}</label>
<select id="branch-switcher" name="branch_id" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:text-white">
@foreach(app(\App\Modules\Core\Services\BranchChoices::class)->all() as $id => $name)<option value="{{ $id }}" @selected($id === app(\App\Modules\Core\Services\BusinessContext::class)->branchId)>{{ $name }}</option>@endforeach
</select></form>
<form method="POST" action="{{ route('business.language') }}">@csrf
<label for="language-switcher" class="sr-only">{{ __('business.language') }}</label>
<select id="language-switcher" name="locale" onchange="this.form.submit()" class="rounded-lg border-gray-300 text-sm dark:bg-gray-900 dark:text-white"><option value="en" @selected(app()->getLocale() === 'en')>English</option><option value="bn" @selected(app()->getLocale() === 'bn')>বাংলা</option></select></form>
@endif