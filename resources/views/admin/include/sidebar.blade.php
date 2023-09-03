<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu border-end">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-dark">
            <span class="logo-sm">
                {{-- <img src="{{ asset($webAssetsContent->favicon) }}" alt="logo" height="22"> --}}
            </span>
            <span class="logo-lg">
                {{-- <img src="{{ asset($webAssetsContent->logo) }}" alt="logo" height="17"> --}}
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('dashboard') }}" class="logo logo-light">
            <span class="logo-sm">
                {{-- <img src="{{ asset($webAssetsContent->favicon) }}" alt="logo" height="22"> --}}
            </span>
            <span class="logo-lg">
                {{-- <img src="{{ asset($webAssetsContent->logo) }}" alt="logo" height="17"> --}}
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            <ul class="navbar-nav" id="navbar-nav">

                <li class="menu-title"><i class="ri-more-fill"></i> <span >Users </span></li>

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarUser" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarUser">
                        <i class="mdi mdi-account-circle-outline"></i> <span >User Management</span>
                    </a>
                    <div class="collapse menu-dropdown {{ (request()->routeIs('add.user')) ||  (request()->routeIs('user.lists'))  ? 'show' : '' }}" id="sidebarUser">
                        <ul class="nav nav-sm flex-column">

                            <li class="nav-item">
                                <a href="{{ route('add.user') }}" class="nav-link {{ (request()->routeIs('add.user')) ? 'active' : '' }}">Add User</a>
                            </li>

                            <li class="nav-item">
                                <a href="{{ route('user.lists') }}" class="nav-link {{ (request()->routeIs('user.lists')) ? 'active' : '' }}">User Lists</a>
                            </li>


                        </ul>
                    </div>
                </li>

                <li class="menu-title"><i class="ri-more-fill"></i> <span>Truck</span></li>

                <li class="nav-item">
                    <a class="nav-link {{ (request()->routeIs('truckList.load.allData')) ? 'active' : '' }}" href="{{ route('truckList.load.allData') }}">
                        <i class="ri-file-list-3-line"></i> <span>Truck List</span>
                    </a>
                </li>

                <li class="menu-title"><i class="ri-more-fill"></i> <span>Cargo</span></li>

                <li class="nav-item">
                    <a class="nav-link {{ (request()->routeIs('cargoList.load.allData')) ? 'active' : '' }}" href="{{ route('cargoList.load.allData') }}">
                        <i class="ri-file-list-3-line"></i> <span>Cargo List</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ (request()->routeIs('cargoInfo.load.allData')) ? 'active' : '' }}" href="{{ route('cargoInfo.load.allData') }}">
                        <i class="ri-file-list-3-line"></i> <span>Cargo Info</span>
                    </a>
                </li>

                <li class="menu-title"><i class="ri-more-fill"></i> <span>Optimum Solution</span></li>

                <li class="nav-item">
                    <a class="nav-link {{ (request()->routeIs('distributeCargo.load.allData')) ? 'active' : '' }}" href="{{ route('distributeCargo.load.allData') }}">
                        <i class="ri-file-list-3-line"></i> <span>Distribute Cargo</span>
                    </a>
                </li>

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
