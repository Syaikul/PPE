<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gudang</title>
    <link rel="icon" type="image/png" sizes="128x128" href="{{ asset('images/favicon.png') }}?v=2">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>

<body>
    @php
        $ikonGudang = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/></svg>';
        $user = auth()->user();
    @endphp

    <main class="hg-main">

        <div class="hg-topbar">
            <div>
                <div class="hg-user">{{ $user->name }}</div>
                <div class="hg-role">{{ \App\Services\AccessControl::roleLabel($user->role) }} · {{ $user->gudangLabel() }}</div>
            </div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="hg-logout">Keluar</button>
            </form>
        </div>

        <div class="hg-header">
            <h1>Daftar Gudang</h1>
        </div>

        @if(session('error'))
            <div class="hg-alert" role="alert">{{ session('error') }}</div>
        @endif

        <div class="hg-grid">
            @forelse ($gudangs as $gudang)
                <a href="{{ route('gudang.enter', $gudang['idgudang']) }}"
                    class="hg-kartu {{ empty($gudang['bisa_akses']) ? 'hg-kartu-terkunci' : '' }}">
                    <div class="hg-kartu-atas">
                        <span class="hg-ikon">{!! $ikonGudang !!}</span>
                        @if(! empty($gudang['bisa_akses']))
                            <span class="hg-badge">Aktif</span>
                        @else
                            <span class="hg-badge hg-badge-kunci">Tidak ada akses</span>
                        @endif
                    </div>
                    <h3 class="hg-nama">{{ $gudang['namagudang'] }}</h3>
                    <p class="hg-kontrak">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                            <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                            <path d="M10 9H8" />
                            <path d="M16 13H8" />
                            <path d="M16 17H8" />
                        </svg>
                        No Kontrak : {{ $gudang['nomorkontrak'] }}
                    </p>
                    <div class="hg-kartu-bawah">
                        <span>ID: GDG-{{ str_pad($gudang['idgudang'], 3, '0', STR_PAD_LEFT) }}</span>
                        <span class="hg-masuk">{{ ! empty($gudang['bisa_akses']) ? 'Masuk →' : 'Terkunci' }}</span>
                    </div>
                </a>
            @empty
                <div class="hg-kosong">
                    {!! $ikonGudang !!}
                    <p>
                        Belum ada data gudang.
                        <br>
                        @canCrud('master_sync')
                            Jalankan <a href="{{ route('master.sync') }}">Sync Data Master</a> untuk menarik data dari API.
                        @else
                            Minta SuperAdmin menjalankan Sync Data Master.
                        @endcanCrud
                    </p>
                </div>
            @endforelse
        </div>
    </main>
</body>

</html>
