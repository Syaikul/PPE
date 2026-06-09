@extends('layouts.kai')

@section('page_title', 'Mobilisasi ' . ($mobilisasi->sr ? 'SR: '.$mobilisasi->sr : '#'.$mobilisasi->id))

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('gudang.mobilisasi', $idgudang) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
    <a href="{{ route('gudang.mobilisasi.perlengkapan', [$idgudang, $mobilisasi->id]) }}"
        class="btn btn-info text-white rounded-pill px-4">
        <i class="fas fa-box-open me-1"></i> Data Perlengkapan Mobilisasi
    </a>
</div>

{{-- Header info --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><span class="text-muted">SR</span><div class="fw-bold">{{ $mobilisasi->sr ?: '-' }}</div></div>
            <div class="col-md-4"><span class="text-muted">Lokasi Pekerjaan</span><div class="fw-bold">{{ $mobilisasi->lokasi_pekerjaan ?: '-' }}</div></div>
            <div class="col-md-4">
                <span class="text-muted">Status</span>
                <div>
                    @php
                        $badge = $mobilisasi->status === 'berjalan' ? 'bg-info' : ($mobilisasi->status === 'selesai' ? 'bg-success' : 'bg-warning text-dark');
                        $label = $mobilisasi->status === 'berjalan' ? 'Sedang Berjalan' : ucfirst($mobilisasi->status);
                    @endphp
                    <span class="badge {{ $badge }}">{{ $label }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Personel Mobilisasi</h4>
        @if($semuaSubmitted && $mobilisasi->status === 'draft')
            <span class="badge bg-success">Semua personel sudah submit pengecekan</span>
        @endif
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Posisi yang digunakan</th>
                        <th class="text-center">Pengecekan Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row['nama'] }}</td>
                            <td>{{ $row['posisi_lbl'] ?: '-' }}</td>
                            <td class="text-center">
                                @if($row['mp']->submitted_at)
                                    <span class="badge bg-primary">Ter-submit</span>
                                @else
                                    <a href="{{ route('gudang.mobilisasi.pengecekan', [$idgudang, $mobilisasi->id, $row['mp']->id]) }}"
                                        class="btn btn-sm {{ $row['lengkap'] ? 'btn-success' : 'btn-outline-danger' }}">
                                        {{ $row['lengkap'] ? 'Lengkap' : 'Tidak Lengkap' }}
                                        <span class="badge bg-light text-dark ms-1">{{ $row['ada'] }}/{{ $row['total'] }}</span>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">Belum ada personel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($bisaJalankan)
    <div class="card shadow-sm mt-3 border-primary">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Semua personel sudah menyelesaikan pengecekan</h5>
                <p class="text-muted mb-0 small">Klik tombol di samping untuk menjalankan proyek dan memindahkan data ke Demobilisasi.</p>
            </div>
            <form action="{{ route('gudang.mobilisasi.jalankan', [$idgudang, $mobilisasi->id]) }}" method="POST"
                onsubmit="return confirm('Jalankan proyek ini? SR akan berstatus Sedang Berjalan.')">
                @csrf
                <button type="submit" class="btn btn-primary btn-lg px-4">
                    <i class="fas fa-play me-1"></i> Jalankan Projek
                </button>
            </form>
        </div>
    </div>
@endif

@endsection
