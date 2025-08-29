<!-- Sidebar Start -->

<aside class="left-sidebar">
  <!-- Sidebar scroll-->
  <div>
    <!-- Sidebar navigation-->
    <nav class="sidebar-nav scroll-sidebar container-fluid">
      <ul id="sidebarnav">
        <!-- ============================= -->
        <!-- Home -->
        <!-- ============================= -->
        <li class="nav-small-cap">
          <i class="ti ti-dots nav-small-cap-icon fs-4"></i>
        </li>
            <li class="sidebar-item">
              <a class="sidebar-link " href="{{  route('home') }}" aria-expanded="false" >
                <span>
                   <i class="fa-duotone fa-house"></i>
                </span>
                <span class="hide-menu">Inicio</span>
              </a>
            </li>

          <li class="sidebar-item">
              <a class="sidebar-link has-arrow" href="javascript:void(0)" aria-expanded="false">
                <span>
                 <i class="fa-duotone fa-headset"></i>
                </span>
                  <span class="hide-menu">Documentos</span>
              </a>
              <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                      <a href="{{ route('administrative.documents') }}" class="sidebar-link">
                          <span class="hide-menu">Documentos</span>
                      </a>
                  </li>
              </ul>
          </li>

          <li class="sidebar-item">
              <a class="sidebar-link has-arrow" >
            <span>
              <i class="fa-duotone fa-note"></i>
            </span>
                  <span class="hide-menu">Configuración</span>
              </a>
              <ul aria-expanded="false" class="collapse first-level">
                  <li class="sidebar-item">
                      <a href="{{ route('callcenter.settings.profile') }}" class="sidebar-link">
                          <span class="hide-menu">Usuario</span>
                      </a>
                  </li>
                  <li class="sidebar-item">
                      <a href="{{ route('callcenter.settings.notifications') }}" class="sidebar-link">
                          <span class="hide-menu">Notificaciones</span>
                      </a>
                  </li>
              </ul>
          </li>
      </ul>
    </nav>
    <!-- End Sidebar navigation -->
  </div>

</aside>

<!-- Sidebar End -->
