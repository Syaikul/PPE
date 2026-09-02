<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>{{ $documentTitle ?? 'Inventory' }}</title>
    <meta content="width=device-width, initial-scale=1.0, shrink-to-fit=no" name="viewport" />
    <link rel="icon" type="image/png" sizes="128x128" href="{{ asset('images/favicon.png') }}?v=2">

    <!-- Fonts and icons (semua lokal, tidak butuh internet) -->
    <link rel="stylesheet" href="{{ asset('template') }}/assets/css/public-sans.css" />
    <link rel="stylesheet" href="{{ asset('template') }}/assets/css/fonts.min.css" />

    <!-- CSS Files -->
    <link rel="stylesheet" href="{{ asset('template') }}/assets/css/bootstrap.min.css" />
    <link rel="stylesheet" href="{{ asset('template') }}/assets/css/plugins.min.css" />
    <link rel="stylesheet" href="{{ asset('template') }}/assets/css/kaiadmin.min.css" />
</head>

<body>

    <div class="wrapper">
        <!-- Sidebar -->
        <x-Sidebar />>
        <!-- End Sidebar -->

        <div class="main-panel">
            <div class="main-header">
                <div class="main-header-logo">
                    <!-- Logo Header -->
                    <div class="logo-header" data-background-color="dark">
                        <a href="index.html" class="logo">
                            <img src="{{ asset('template') }}/assets/img/kaiadmin/logo_light.svg" alt="navbar brand"
                                class="navbar-brand" height="20" />
                        </a>
                        <div class="nav-toggle">
                            <button class="btn btn-toggle toggle-sidebar p-7">
                                <i class="gg-menu-right"></i>
                            </button>
                            <button class="btn btn-toggle sidenav-toggler p-7">
                                <i class="gg-menu-left"></i>
                            </button>
                        </div>
                        <button class="topbar-toggler more p-7">
                            <i class="gg-more-vertical-alt"></i>
                        </button>
                    </div>
                    <!-- End Logo Header -->
                </div>
                <!-- Navbar Header -->
                <nav class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
                    <div class="container-fluid">


                        <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
                            <li class="nav-item topbar-user dropdown hidden-caret">
                                <a class="dropdown-toggle profile-pic" data-bs-toggle="dropdown" href="#"
                                    aria-expanded="false">
                                    <div class="avatar-sm">
                                        <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                                            class="avatar-img rounded-circle" style="object-fit:cover"
                                            referrerpolicy="no-referrer"
                                            onerror="this.onerror=null;this.src='{{ asset('template') }}/assets/img/avatar-default.png';" />
                                    </div>
                                    <span class="profile-username">
                                        <span class="op-7">Hi,</span>
                                        <span class="fw-bold">{{ auth()->user()->name }}</span>
                                        <span class="d-block small op-7">{{ \App\Services\AccessControl::roleLabel(auth()->user()->role) }}</span>
                                    </span>
                                </a>
                                <ul class="dropdown-menu dropdown-user animated fadeIn">
                                    <div class="dropdown-user-scroll scrollbar-outer">
                                        <li>
                                            <div class="user-box">
                                                <div class="avatar-lg">
                                                    <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}"
                                                        class="avatar-img rounded" style="object-fit:cover"
                                                        referrerpolicy="no-referrer"
                                                        onerror="this.onerror=null;this.src='{{ asset('template') }}/assets/img/avatar-default.png';" />
                                                </div>
                                                <div class="u-text">
                                                    <h4>{{ auth()->user()->name }}</h4>
                                                    <p class="text-muted mb-1">{{ auth()->user()->email }}</p>
                                                    <p class="small mb-0">{{ \App\Services\AccessControl::roleLabel(auth()->user()->role) }}</p>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">My Profile</a>
                                            <a class="dropdown-item" href="#">My Balance</a>
                                            <a class="dropdown-item" href="#">Inbox</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Account Setting</a>
                                            <div class="dropdown-divider"></div>
                                            <div>
                                                <a class="dropdown-item" href="{{ route('logout') }}"
                                                    onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                                    {{ __('Logout') }}
                                                </a>

                                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                    class="d-none">
                                                    @csrf
                                                </form>
                                            </div>

                                        </li>
                                    </div>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>
                <!-- End Navbar -->
            </div>

            <div class="container">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">@yield('page_title', 'Inventory App')</h4>
                    </div>
                    @yield('content')
                </div>
            </div>

            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <nav class="pull-left">
                        <ul class="nav">
                            <li class="nav-item">
                                <a class="nav-link" href="http://www.themekita.com">
                                    Need Help?
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="copyright">
                        2025, Created by <a href="https://www.linkedin.com/in/syaikul-ibnu-690870295/">Syaikul Ibnu</a>
                    </div>
                    <div>
                        Distributed by
                        <a target="_blank" href="https://www.mesitechmitra.co.id/">Mesitechmitra</a>.
                    </div>
                </div>
            </footer>
        </div>

        <!-- Custom template | don't include it in your project! -->

    </div>
    <!--   Core JS Files   -->
    <script src="{{ asset('template') }}/assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('template') }}/assets/js/core/popper.min.js"></script>
    <script src="{{ asset('template') }}/assets/js/core/bootstrap.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="{{ asset('template') }}/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Datatables -->
    <script src="{{ asset('template') }}/assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Kaiadmin JS -->
    <script src="{{ asset('template') }}/assets/js/kaiadmin.min.js"></script>

    @stack('scripts')

</body>

</html>
