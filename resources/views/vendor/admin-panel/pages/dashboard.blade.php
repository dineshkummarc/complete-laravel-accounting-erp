@extends('admin-panel::layouts.app')
@section('content')
<h1>{{ __('platform.console') }}</h1>
<x-admin-panel::card>
<p>{{ __('platform.foundation_status') }}</p>
<dl><dt>{{ __('platform.users') }}</dt><dd>{{ \App\Models\User::query()->count() }}</dd>
<dt>{{ __('platform.failed_jobs') }}</dt><dd>{{ \Illuminate\Support\Facades\DB::table('failed_jobs')->count() }}</dd></dl>
<a href="/app">{{ __('platform.application') }}</a> · <a href="/drutogoti">{{ __('platform.performance') }}</a>
</x-admin-panel::card>
@endsection
