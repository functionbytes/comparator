
<div class="headbar d-flex">
    <div class="me-auto"></div>
    <div class="top-search-container"></div>

    @include('layouts.core._quick_change_theme_mode', [
        'mode' => 'dark',
        'url' => '',
    ])

</div>

<script>
    $(function() {
        TopSearchBar.init({
            container: $('.top-search-container'),
            sections: [

                new SearchSection({
                    url: '{{ route('manager.subscribers') }}',
                }),
                new SearchSection({
                    url: '{{ route('manager.templates') }}',
                }),
            ],
            lang: {
                no_keyword: `{!! trans('messages.search.type_to_search.wording') !!}`,
                empty_result: `{!! trans('messages.search.empty_result') !!}`,
                tooltip: `{!! trans('messages.click_open_app_search') !!}`,
            }
        });
    });
</script>
