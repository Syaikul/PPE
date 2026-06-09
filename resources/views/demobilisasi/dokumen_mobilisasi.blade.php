@extends('layouts.kai')

@section('page_title', 'Dokumen Mobilisasi — ' . $nama)

@section('content')

<div class="mb-3">
    <a href="{{ route('gudang.demobilisasi', $idgudang) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Demobilisasi
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title mb-1">Dokumen Mobilisasi</h4>
        <div class="text-muted small">SR: {{ $mobilisasi->sr ?: '-' }} &middot; Lokasi: {{ $mobilisasi->lokasi_pekerjaan ?: '-' }}</div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6"><span class="text-muted">Nama Personel</span><div class="fw-bold fs-5">{{ $nama }}</div></div>
            <div class="col-md-6"><span class="text-muted">Posisi</span><div class="fw-bold">{{ $posisiLbl ?: '-' }}</div></div>
        </div>

        <h6 class="text-muted mb-2">Item yang Dibawa</h6>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">No</th>
                        <th>Nama PPE</th>
                        <th>Kategori</th>
                        <th class="text-center" style="width:120px">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $item['label'] }}</td>
                            <td>
                                <span class="badge {{ $item['kategori'] === 'Consumable' ? 'bg-info' : 'bg-secondary' }}">
                                    {{ $item['kategori'] }}
                                </span>
                            </td>
                            <td class="text-center">{{ $item['jumlah'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada item yang dibawa.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
