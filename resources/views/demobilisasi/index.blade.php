@extends('layouts.kai')

@section('page_title', 'Demobilisasi — ' . ($gudang['namagudang'] ?? 'Gudang'))

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

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-warehouse me-1"></i> Ganti Gudang
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">{{ $gudang['namagudang'] ?? 'Gudang #'.$idgudang }}</span>
</div>

@php
    if (! function_exists('demobStatusInfo')) {
    function demobStatusInfo($mp) {
        $s = $mp->demob_status;
        if ($s === null) {
            return ['kondisi' => 'OnSite', 'kondisi_badge' => 'bg-success', 'status' => 'Pengecekan belum bisa dilakukan', 'status_class' => 'text-muted'];
        }
        if ($s === 'belum_cek') {
            return ['kondisi' => 'OffSite', 'kondisi_badge' => 'bg-secondary', 'status' => 'Pengecekan belum dilakukan', 'status_class' => 'text-dark'];
        }
        if ($s === 'menunggu_approval') {
            return ['kondisi' => 'OffSite', 'kondisi_badge' => 'bg-secondary', 'status' => 'Menunggu Approval', 'status_class' => 'text-warning fw-semibold'];
        }
        return ['kondisi' => 'OffSite', 'kondisi_badge' => 'bg-secondary', 'status' => 'Selesai', 'status_class' => 'text-success fw-semibold'];
    }
    }
@endphp

@forelse($mobilisasiList as $mob)
    @php
        $semuaSelesai = $mob->personel->every(fn($mp) => $mp->demob_status === 'selesai');
        $adaOnsite = $mob->personel->contains(fn($mp) => $mp->demob_status === null);
        if ($semuaSelesai) { $srBadge = 'bg-success'; $srLabel = 'Selesai'; }
        elseif ($adaOnsite) { $srBadge = 'bg-info'; $srLabel = 'Sedang Berjalan'; }
        else { $srBadge = 'bg-warning text-dark'; $srLabel = 'Tahap Pengecekan'; }
    @endphp
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <div class="fw-bold">SR : {{ $mob->sr ?: '-' }}</div>
            <div>Status : <span class="badge {{ $srBadge }}">{{ $srLabel }}</span></div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nama Personel</th>
                        <th>Posisi</th>
                        <th class="text-center">Kondisi</th>
                        <th class="text-center">Tanggal Demob</th>
                        <th>Status</th>
                        <th class="text-center">Dokumen</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mob->rows as $row)
                        @php $info = demobStatusInfo($row['mp']); $mp = $row['mp']; @endphp
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $row['nama'] }}</td>
                            <td class="fw-semibold">{{ $row['posisi_lbl'] ?: '-' }}</td>
                            <td class="text-center"><span class="badge {{ $info['kondisi_badge'] }}">{{ $info['kondisi'] }}</span></td>
                            <td class="text-center">{{ $mp->tanggal_demob ? $mp->tanggal_demob->format('d M y') : '-' }}</td>
                            <td class="{{ $info['status_class'] }}">{{ $info['status'] }}</td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('gudang.demobilisasi.dokumen-mob', [$idgudang, $mob->id, $mp->id]) }}"
                                    class="btn btn-sm btn-outline-primary mb-1">
                                    <i class="fas fa-file-alt me-1"></i> Mobilisasi
                                </a>
                                <br>
                                @if(in_array($mp->demob_status, ['menunggu_approval', 'selesai']))
                                    <a href="{{ route('gudang.demobilisasi.dokumen-demob', [$idgudang, $mob->id, $mp->id]) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-file-alt me-1"></i> Demobilisasi
                                    </a>
                                @else
                                    <span class="btn btn-sm btn-outline-secondary disabled">
                                        <i class="fas fa-file-alt me-1"></i> Demobilisasi
                                    </span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                @if($mp->demob_status === null)
                                    <form action="{{ route('gudang.demobilisasi.selesaikan', [$idgudang, $mob->id, $mp->id]) }}"
                                        method="POST" onsubmit="return confirm('Selesaikan (demob) personel ini? Personel akan OffSite.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-3">Selesaikan</button>
                                    </form>
                                @elseif($mp->demob_status === 'belum_cek')
                                    <a href="{{ route('gudang.demobilisasi.cek', [$idgudang, $mob->id, $mp->id]) }}"
                                        class="btn btn-sm btn-success rounded-pill px-3">Cek Kelengkapan Personel</a>
                                @elseif($mp->demob_status === 'menunggu_approval')
                                    <span class="badge bg-warning text-dark">Menunggu Approval</span>
                                @else
                                    <span class="badge bg-success">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-truck fa-2x mb-3 opacity-25 d-block"></i>
            Belum ada mobilisasi yang berjalan. Jalankan proyek di menu Mobilisasi terlebih dahulu.
        </div>
    </div>
@endforelse

@endsection
