<div class="sidebar" style="background-color: #e8f0ec !important; color: white;">
    <div class="sidebar-logo">

        <!-- Logo Header -->
        <div class="logo-header" style="background-color: #e8f0ec !important; color: white;">
            <a href="{{ route('home') }}" class="logo">
                <img src="{{ asset('images/logo-mesitech.png') }}" alt="navbar brand"
                    class="navbar-brand" height="40" />
            </a>

            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>

                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>

            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
        <!-- End Logo Header -->

    </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">

            <ul class="nav nav-secondary">

                <li class="nav-item">
                    <a href="/dashboard">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#datastok" class="collapsed" aria-expanded="false">
                        <i class="fas fa-desktop"></i>
                        <p>Data Stok</p>
                        <span class="caret"></span>
                    </a>

                    <div class="collapse" id="datastok">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.ppe-masuk', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">PPE Masuk</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.ppe-keluar', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">PPE Keluar</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.stok', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Stok</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.transfer-barang', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Transfer Barang</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#personel" class="collapsed" aria-expanded="false">
                        <i class="fas fa-users"></i>
                        <p>Personel</p>
                        <span class="caret"></span>
                    </a>

                    <div class="collapse" id="personel">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.personel', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Data Personel</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.pemakaian-ppe', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Data Pemakaian PPE</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                @if(auth()->user()->canView('permintaan') || auth()->user()->canCrud('permintaan_buat'))
                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#permintaanppe" class="collapsed" aria-expanded="false">
                        <i class="fas fa-clipboard-list"></i>
                        <p>Permintaan PPE</p>
                        <span class="caret"></span>
                    </a>

                    <div class="collapse" id="permintaanppe">
                        <ul class="nav nav-collapse">
                            @canCrud('permintaan_buat')
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.permintaan-ppe.create', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Buat Tabel Permintaan</span>
                                </a>
                            </li>
                            @endcanCrud
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.permintaan', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Data Permintaan</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                @endif

                <li class="nav-item">
                    <a data-bs-toggle="collapse" href="#mobdemob" class="collapsed" aria-expanded="false">
                        <i class="fas fa-truck"></i>
                        <p>Mob-Demob</p>
                        <span class="caret"></span>
                    </a>

                    <div class="collapse" id="mobdemob">
                        <ul class="nav nav-collapse">
                            @canCrud('approval_demob')
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.approval-demob', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Approval Demob</span>
                                </a>
                            </li>
                            @endcanCrud
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.mobilisasi', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Mobilisasi</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ session('idgudang') ? route('gudang.demobilisasi', session('idgudang')) : route('home') }}">
                                    <span class="sub-item">Demobilisasi</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <li class="nav-item">
                    <a href="{{ session('idgudang') ? route('gudang.peminjaman-ppe', session('idgudang')) : route('home') }}">
                        <i class="fas fa-handshake"></i>
                        <p>Peminjaman PPE</p>
                    </a>
                </li>

                @canCrud('master_sync')
                <li class="nav-item">
                    <a href="{{ route('master.sync') }}">
                        <i class="fas fa-sync-alt"></i>
                        <p>Sync Data Master</p>
                    </a>
                </li>
                @endcanCrud

                @canCrud('users')
                <li class="nav-item">
                    <a href="{{ route('users.index') }}">
                        <i class="fas fa-user-shield"></i>
                        <p>Kelola Akun &amp; Role</p>
                    </a>
                </li>
                @endcanCrud

            </ul>

        </div>
    </div>
</div>
