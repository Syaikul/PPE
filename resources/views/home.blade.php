<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Gudang</title>
    <link rel="icon" href="{{ asset('template') }}/assets/img/kaiadmin/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
</head>

<body>
    @php
        $ikonGudang = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12"/><path d="M6 14h12"/><rect width="12" height="12" x="6" y="10"/></svg>';
    @endphp

    <main class="hg-main">

        <div class="hg-header">
            <h1>Daftar Gudang</h1>
        </div>

        <div class="hg-grid">
            @forelse ($gudangs as $gudang)
                <a href="{{ route('gudang.stok', $gudang['idgudang']) }}" class="hg-kartu">
                    <div class="hg-kartu-atas">
                        <span class="hg-ikon">{!! $ikonGudang !!}</span>
                        <span class="hg-badge">Aktif</span>
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
                        <span class="hg-masuk">Masuk &rarr;</span>
                    </div>
                </a>
            @empty
                <div class="hg-kosong">
                    {!! $ikonGudang !!}
                    <p>
                        Belum ada data gudang.
                        <br>
                        Jalankan <a href="{{ route('master.sync') }}">Sync Data Master</a> untuk menarik data dari API.
                    </p>
                </div>
            @endforelse
        </div>
    </main>
</body>

</html>
