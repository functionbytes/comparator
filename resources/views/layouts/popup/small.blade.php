<div class="modal-dialog modal-lg modal-dialog-centered  modal-default modal-templates">
    <div class="modal-content">
        <div class="modal-header">
            <a href="javascript:;" class="material-symbols-rounded back">
                <i class="fa-solid fa-arrow-left"></i>
            </a>
            <h5 class="modal-title text-center" style="width:100%">
                @yield('bar-title')
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="modal-body @yield('class')">
            @hasSection('title')
                <h4 class="fw-600 mb-3 pb-1">@yield('title')</h4>
            @endif

            <!-- display flash message -->
            @include('common.errors')

            <!-- main inner content -->
            @yield('content')
        </div>
    </div>
</div>
