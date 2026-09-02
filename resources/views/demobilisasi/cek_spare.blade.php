@extends('layouts.kai')

@section('page_title', 'Pengecekan Spare Barang — ' . $nama)

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
    <a href="{{ route('gudang.demobilisasi', $idgudang) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
    <div>Personel: <strong>{{ $nama }}</strong></div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <div class="row">
            <div class="col-md-4">
                <span class="text-muted">SR</span>
                <div class="fw-bold">{{ $mobilisasi->sr ?: '-' }}</div>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Tanggal Mobilisasi</span>
                <div class="fw-bold">{{ $mobilisasi->created_at?->format('d M Y') ?: '-' }}</div>
            </div>
            <div class="col-md-4">
                <span class="text-muted">Lokasi</span>
                <div class="fw-bold">{{ $mobilisasi->lokasi_pekerjaan ?: '-' }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-box-open me-2"></i>Pengecekan Spare Barang</h5>
        <small class="text-muted">Isi sisa yang dikembalikan ke gudang. Selisih (jumlah − sisa) langsung tercatat di PPE Keluar atas nama penanggung jawab.</small>
    </div>
    @include('spare_barang._daftar', [
        'showActions' => true,
    ])
</div>

@endsection
