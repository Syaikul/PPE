@extends('layouts.kai')

@section('page_title', 'Pengecekan — ' . $nama)

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
    <a href="{{ route('gudang.mobilisasi.show', [$idgudang, $mobilisasi->id]) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
    <div>
        <span class="me-2">Personel: <strong>{{ $nama }}</strong></span>
        @if($mp->submitted_at)
            <span class="badge bg-primary">Sudah di-submit</span>
        @elseif($lengkap)
            <span class="badge bg-success">Lengkap</span>
        @else
            <span class="badge bg-warning text-dark">Tidak Lengkap</span>
        @endif
    </div>
</div>

@php
    $sections = [
        ['Pengecekan PPE (Personal Protective Equipment)', $itemsPpe, 'Status PPE'],
        ['Pengecekan Consumable', $itemsConsumable, 'Status'],
    ];
@endphp

@foreach($sections as [$judul, $items, $statusLabel])
    <div class="card shadow-sm mb-3">
        <div class="card-header"><h5 class="card-title mb-0">{{ $judul }}</h5></div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nama PPE</th>
                        <th class="text-center" style="width:100px">Jumlah</th>
                        <th class="text-center" style="width:160px">{{ $statusLabel }}</th>
                        <th style="width:30%">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $item['label'] }}</td>
                            <td class="text-center">{{ $item['jumlah'] }}</td>
                            <td class="text-center">
                                @if($item['status'] === 'ada')
                                    @if($item['from_keluar'] ?? false)
                                        <span class="btn btn-sm btn-success px-3 disabled" title="Sudah pernah diterima dari PPE Keluar">Ada</span>
                                        <small class="d-block text-muted mt-1">Sudah punya</small>
                                    @else
                                        <form action="{{ route('gudang.mobilisasi.pengecekan.update', [$idgudang, $mobilisasi->id, $mp->id]) }}"
                                            method="POST" class="d-inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="idsubbarang" value="{{ $item['idsubbarang'] }}">
                                            <input type="hidden" name="action" value="tidak">
                                            <button type="submit" {{ $mp->submitted_at ? 'disabled' : '' }}
                                                class="btn btn-sm btn-success px-3" title="Klik untuk batalkan">Ada</button>
                                        </form>
                                    @endif
                                @else
                                    <form action="{{ route('gudang.mobilisasi.pengecekan.update', [$idgudang, $mobilisasi->id, $mp->id]) }}"
                                        method="POST" class="d-inline">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="idsubbarang" value="{{ $item['idsubbarang'] }}">
                                        <input type="hidden" name="action" value="ada">
                                        <button type="submit" {{ $mp->submitted_at ? 'disabled' : '' }}
                                            class="btn btn-sm btn-outline-success px-3">Tambahkan +</button>
                                    </form>
                                @endif
                            </td>
                            <td>{{ $item['catatan'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Tidak ada item.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach

@if(! $mp->submitted_at)
    <div class="d-flex justify-content-end gap-2">
        <form action="{{ route('gudang.mobilisasi.pengecekan.submit', [$idgudang, $mobilisasi->id, $mp->id]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary px-4" {{ $lengkap ? '' : 'disabled' }}>
                Submit Pengecekan
            </button>
        </form>
    </div>
    @unless($lengkap)
        <p class="text-end text-muted small mt-2">Semua status harus "Ada" agar bisa submit.</p>
    @endunless
@endif

@endsection
