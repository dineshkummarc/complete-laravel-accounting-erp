<!DOCTYPE html><html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><title>{{ __('platform.performance') }}</title></head><body>
<h1>{{ __('platform.performance') }}</h1>
<dl>@foreach($summary as $key => $value)<dt>{{ __('platform.'.$key) }}</dt><dd>{{ is_bool($value) ? ($value ? __('platform.yes') : __('platform.no')) : $value }}</dd>@endforeach</dl>
<a href="/admin">{{ __('platform.console') }}</a>
</body></html>
