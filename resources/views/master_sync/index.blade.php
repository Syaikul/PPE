@extends('layouts.kai')

@section('page_title', 'Sync Data Master')

@section('content')

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4 class="card-title mb-1">Sync Data Master</h4>
            <small class="text-muted">Sumber API: <code>{{ $baseUrl }}</code></small>
        </div>
        <form method="POST" action="{{ route('master.sync.run') }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = 'Mengambil data...';">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sync-alt me-1"></i> Sync Semua Sekarang
            </button>
        </form>
    </div>
    <div class="card-body">
        <p class="text-muted">
            Aplikasi membaca data master dari salinan di database ini, bukan dari API.
            Jalankan sync hanya ketika data master (gudang, personel, posisi, barang/varian) berubah.
            Kalau sync gagal, data lama tetap dipakai sehingga aplikasi tidak ikut mati.
        </p>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th class="text-center">Jumlah Baris</th>
                        <th>Terakhir Sync</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($status as $s)
                        <tr>
                            <td class="fw-semibold">
                                {{ $s['label'] }}
                                <div><small class="text-muted"><code>{{ $s['endpoint'] }}</code></small></div>
                            </td>
                            <td class="text-center">
                                @if ($s['ada'])
                                    <span class="badge bg-success">{{ $s['jumlah'] }}</span>
                                @else
                                    <span class="badge bg-secondary">Kosong</span>
                                @endif
                            </td>
                            <td>
                                @if ($s['synced_at'])
                                    {{ $s['synced_at']->format('d/m/Y H:i') }}
                                    <div><small class="text-muted">{{ $s['synced_at']->diffForHumans() }}</small></div>
                                @else
                                    <span class="text-danger">Belum pernah di-sync</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('master.sync.run') }}" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="endpoint" value="{{ $s['endpoint'] }}">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Sync Ini Saja</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="alert alert-light border mt-3 mb-0">
            <strong>Lewat terminal:</strong>
            <code>php artisan master:sync</code> (semua),
            <code>php artisan master:sync personel</code> (satu data),
            <code>php artisan master:sync --status</code> (cek status).
        </div>
    </div>
</div>

@endsection
