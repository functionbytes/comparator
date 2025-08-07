@if (\App\Models\Setting::get('site_favicon'))
    <link rel="shortcut icon" type="image/png" href="{{ route('SettingController@file', \App\Models\Setting::get('site_favicon')) }}"/>
@else
    @include('layouts.core._favicon_default')
@endif
