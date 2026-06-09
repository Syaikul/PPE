@extends('layouts.kai')

@section('page_title', 'Dokumen Demobilisasi — ' . $nama)

@section('content')

@php
    $kondisiBadge = ['layak' => 'bg-success', 'tidak_layak' => 'bg-warning text-dark', 'hilang' => 'bg-danger'];
    $kondisiLabel = ['layak' => 'Layak', 'tidak_layak' => 'Tidak Layak', 'hilang' => 'Hilang'];
@endphp

<div class="mb-3">
    <a href="{{ route('gudang.demobilisasi', $idgudang) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Demobilisasi
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title mb-1">Dokumen Demobilisasi</h4>
        <div class="text-muted small">SR: {{ $mobilisasi->sr ?: '-' }}</div>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6"><span class="text-muted">Nama Personel</span><div class="fw-bold fs-5">{{ $nama }}</div></div>
            <div class="col-md-6">
                <span class="text-muted">Tanggal Demob</span>
                <div class="fw-bold">{{ $mp->tanggal_demob ? $mp->tanggal_demob->format('d M Y') : '-' }}</div>
            </div>
        </div>

        <h6 class="text-muted mb-2">Hasil Pengecekan Kelengkapan</h6>
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:60px">No</th>
                        <th>Nama PPE</th>
                        <th class="text-center" style="width:160px">Kondisi</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td class="fw-semibold">{{ $item['label'] }}</td>
                            <td class="text-center">
                                <span class="badge {{ $kondisiBadge[$item['kondisi']] ?? 'bg-light text-dark' }}">
                                    {{ $kondisiLabel[$item['kondisi']] ?? '-' }}
                                </span>
                            </td>
                            <td>{{ $item['catatan'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada pengecekan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mp->approval_catatan)
            <div class="alert alert-light border mt-3 mb-0">
                <strong>Catatan Approval:</strong> {{ $mp->approval_catatan }}
            </div>
        @endif
    </div>
</div>

@endsection
