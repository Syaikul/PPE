@extends('layouts.kai')

@section('page_title', 'Detail MR — ' . $permintaan->nomor_mr)

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

<div class="mb-3">
    <a href="{{ route('gudang.permintaan', $idgudang) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

{{-- Header biru seperti gambar 3 --}}
<div class="card shadow-sm mb-4">
    <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:#1a73e8;">
        <div>
            <h4 class="card-title text-white mb-1">Detail Material Request</h4>
            <div class="d-flex gap-3 small opacity-75">
                <span><i class="fas fa-file-alt me-1"></i> {{ $permintaan->nomor_mr }}</span>
                <span><i class="fas fa-calendar me-1"></i> {{ $permintaan->tanggal_permintaan->format('j M Y') }}</span>
            </div>
        </div>
        @php $st = $permintaan->status; @endphp
        <span class="badge px-3 py-2 fs-6
            {{ $st === 'Sudah Selesai' ? 'bg-success' : ($st === 'Sebagian' ? 'bg-info' : 'bg-dark') }}">
            {{ $st === 'Belum Selesai' ? 'Belum Selesai' : $st }}
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px"></th>
                        <th>Nama PPE</th>
                        <th class="text-center">Jumlah</th>
                        <th class="text-center">Sudah Datang</th>
                        <th class="text-center">Sisa</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permintaan->items as $item)
                        @php
                            $nama = $varianMap[$item->idbarangvarian]['label'] ?? 'Barang #'.$item->idbarangvarian;
                            $itemSt = $item->status;
                        @endphp
                        <tr class="item-row" style="cursor:pointer;" data-bs-toggle="collapse"
                            data-bs-target="#collapse-{{ $item->id }}" aria-expanded="false">
                            <td class="text-center text-muted">
                                <i class="fas fa-chevron-down"></i>
                            </td>
                            <td class="fw-semibold">{{ $nama }}</td>
                            <td class="text-center">{{ $item->qty_diminta }}</td>
                            <td class="text-center">{{ $item->qty_datang }}</td>
                            <td class="text-center">{{ $item->sisa }}</td>
                            <td class="text-center">
                                <span class="badge
                                    {{ $itemSt === 'Sudah Selesai' ? 'bg-success' : ($itemSt === 'Sebagian' ? 'bg-primary' : 'bg-dark') }}">
                                    {{ $itemSt }}
                                </span>
                            </td>
                        </tr>
                        <tr class="collapse-row">
                            <td colspan="6" class="p-0 border-0">
                                <div class="collapse" id="collapse-{{ $item->id }}">
                                    <div class="p-4 bg-light border-top">

                                        {{-- Riwayat kedatangan --}}
                                        @if($item->kedatangan->isNotEmpty())
                                            <h6 class="fw-semibold mb-2">Riwayat Kedatangan</h6>
                                            <table class="table table-sm table-bordered bg-white mb-3">
                                                <thead>
                                                    <tr>
                                                        <th>Tanggal</th>
                                                        <th>QTY Datang</th>
                                                        <th>No PO</th>
                                                        <th>Catatan</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($item->kedatangan as $k)
                                                        <tr>
                                                            <td>{{ $k->tanggal->format('j M Y') }}</td>
                                                            <td>{{ $k->qty_datang }}</td>
                                                            <td>{{ $k->no_po ?? '-' }}</td>
                                                            <td>{{ $k->catatan ?? '-' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif

                                        {{-- Form tambah kedatangan --}}
                                        @if($item->sisa > 0)
                                            <h6 class="fw-semibold mb-2">Tambah Kedatangan</h6>
                                            <form action="{{ route('gudang.permintaan.kedatangan', [$idgudang, $permintaan->id, $item->id]) }}"
                                                method="POST" class="row g-3 align-items-end">
                                                @csrf
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">Tanggal</label>
                                                    <input type="date" name="tanggal" class="form-control form-control-sm"
                                                        value="{{ date('Y-m-d') }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold">QTY Datang</label>
                                                    <input type="number" name="qty_datang" class="form-control form-control-sm"
                                                        min="1" max="{{ $item->sisa }}" required>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label small fw-semibold">No PO</label>
                                                    <input type="text" name="no_po" class="form-control form-control-sm"
                                                        placeholder="No PO">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small fw-semibold">Catatan</label>
                                                    <input type="text" name="catatan" class="form-control form-control-sm"
                                                        placeholder="Catatan">
                                                </div>
                                                <div class="col-md-2">
                                                    <button type="submit" class="btn btn-sm btn-primary w-100">Simpan</button>
                                                </div>
                                            </form>
                                        @else
                                            <p class="text-success mb-0 small"><i class="fas fa-check-circle me-1"></i> Barang ini sudah lengkap datang.</p>
                                        @endif

                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
