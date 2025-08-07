@php $menu = $menu ?? false @endphp

<li class="nav-item dropdown">
    <a href="{{ route('manager.templates') }}" class="nav-link d-flex align-items-center ps-3 pe-1 py-3 lvl-1 dropdown-toggle"
        id="content-menu" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="navbar-icon">
            <img src="" style="border-radius:100%;" class="menu-user-avatar" alt="">
        </i>
        <span style="
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
        ">{{Auth::user()->firsname }}</span>

            <i class="material-symbols-rounded customer-warning-icon text-danger" style="right: 3px!important;
            color: rgb(236, 124, 124)!important;
            top: 2px!important;
            position: absolute;
            text-indent: 0;transform:scale(1.2)">info</i>
    </a>
    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-bottom top-user-menus" aria-labelledby="content-menu">
        <li class="backdrop  backdrop-frontend p-4 d-flex align-items-center justify-content-center">
            <img src="{{ url('images/paper-airplane.svg') }}" width="100px" />
        </li>

    </ul>
</li>
