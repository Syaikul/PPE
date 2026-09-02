@extends('layouts.kai')

@section('page_title', 'Dokumen Spare Barang — ' . $nama)

@section('content')

<div class="mb-3">
    <a href="{{ route('gudang.demobilisasi', $idgudang) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Demobilisasi
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title mb-1">Dokumen Spare Barang</h4>
        <div class="text-muted small">
            SR: {{ $mobilisasi->sr ?: '-' }}
            &middot; Lokasi: {{ $mobilisasi->lokasi_pekerjaan ?: '-' }}
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6"><span class="text-muted">Nama Personel</span><div class="fw-bold fs-5">{{ $nama }}</div></div>
            <div class="col-md-6"><span class="text-muted">Tanggal Demob</span><div class="fw-bold">{{ $mp->tanggal_demob ? $mp->tanggal_demob->format('d M Y') : '-' }}</div></div>
        </div>

        <h6 class="text-muted mb-2">Spare Mobilisasi ini</h6>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">No</th>
                        <th>Item</th>
                        <th class="text-center" style="width:90px">Jumlah</th>
                        <th class="text-center" style="width:90px">Sisa</th>
                        <th class="text-center" style="width:90px">Dipakai</th>
                        <th>Penanggung Jawab</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">
                                {{ $item['label'] }}
                                @if($item['returned'])
                                    <span class="badge bg-secondary ms-1">Selesai</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $item['jumlah'] }}</td>
                            <td class="text-center">{{ $item['sisa'] }}</td>
                            <td class="text-center">{{ $item['dipakai'] }}</td>
                            <td>{{ $item['pj'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-3">Tidak ada spare barang untuk mobilisasi ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
