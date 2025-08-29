
<div class="headbar d-flex">
    <div class="me-auto"></div>
    @if(config('app.brand'))
        <div class="me-2">
            <a class="open-site-top-menu xtooltip" title="{{ trans('messages.open_website') }}" target="_blank" href="{{ config('wordpress.url') }}">
                <span class="material-symbols-rounded">public</span>
            </a>
        </div>
    @endif
    <div class="top-search-container"></div>
    @include('layouts.core._quick_change_theme_mode', [
        'mode' => Auth::user()->customer->theme_mode ? Auth::user()->customer->theme_mode : 'light',
        'url' => route('AccountController@changeThemeMode'),
    ])
</div>

<script>
    $(function() {
        TopSearchBar.init({
            container: $('.top-search-container'),
            sections: [
                new SearchSection({
                    url: '{{ route('SearchController@general') }}',
                }),
                new SearchSection({
                    url: '{{ route('SearchController@campaigns') }}',
                }),
                new SearchSection({
                    url: '{{ route('SearchController@lists') }}',
                }),
                new SearchSection({
                    url: '{{ route('SearchController@subscribers') }}',
                }),
                new SearchSection({
                    url: '{{ route('SearchController@automations') }}',
                }),
                new SearchSection({
                    url: '{{ route('SearchController@templates') }}',
                }),
                new SearchSection({
                    url: '{{ route('SearchController@forms') }}',
                }),
                new SearchSection({
                    url: '{{ route('SearchController@websites') }}',
                })
            ],
            lang: {
                no_keyword: `{!! trans('messages.search.type_to_search.wording') !!}`,
                empty_result: `{!! trans('messages.search.empty_result') !!}`,
                tooltip: `{!! trans('messages.click_open_app_search') !!}`,
            }
        });
    });
</script>
