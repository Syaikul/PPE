@extends('layouts.kai')

@section('page_title', 'Approval Demob — ' . ($gudang['namagudang'] ?? 'Gudang'))

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

<h4 class="mb-3">Approval Penggantian PPE</h4>

@php
    $kondisiBadge = ['tidak_layak' => 'bg-warning text-dark', 'hilang' => 'bg-danger'];
    $kondisiLabel = ['tidak_layak' => 'Rusak', 'hilang' => 'Hilang'];
@endphp

@forelse($list as $row)
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                    style="width:48px;height:48px;font-size:1.2rem;">
                    {{ strtoupper(substr($row['nama'], 0, 1)) }}
                </div>
                <div class="ms-3">
                    <div class="fw-bold fs-5">{{ $row['nama'] }}</div>
                    <div class="text-muted small">{{ $row['posisi_lbl'] ?: '-' }}</div>
                </div>
            </div>

            <hr>

            @forelse($row['problems'] as $item)
                <div class="border-start border-4 border-warning ps-3 py-2 mb-2 bg-light">
                    <div class="fw-semibold">{{ $item['label'] }}</div>
                    <div class="small">
                        KONDISI:
                        <span class="badge {{ $kondisiBadge[$item['kondisi']] ?? 'bg-secondary' }}">
                            {{ $kondisiLabel[$item['kondisi']] ?? $item['kondisi'] }}
                        </span>
                    </div>
                    @if($item['catatan'])
                        <div class="small text-muted mt-1">Catatan: {{ $item['catatan'] }}</div>
                    @endif
                </div>
            @empty
                <p class="text-muted">Tidak ada item bermasalah.</p>
            @endforelse

            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="mb-3">Keputusan Approval</h6>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <form action="{{ route('gudang.approval-demob.approve', [$idgudang, $row['mp']->id]) }}" method="POST" class="form-approval">
                                @csrf
                                <input type="hidden" name="catatan" class="catatan-mirror">
                                <button type="submit" class="btn btn-outline-success w-100">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('gudang.approval-demob.reject', [$idgudang, $row['mp']->id]) }}" method="POST" class="form-approval"
                                onsubmit="return confirm('Tolak approval? Personel dikembalikan ke tahap pengecekan.')">
                                @csrf
                                <input type="hidden" name="catatan" class="catatan-mirror">
                                <button type="submit" class="btn btn-outline-danger w-100">
                                    <i class="fas fa-times me-1"></i> Not Approve
                                </button>
                            </form>
                        </div>
                    </div>
                    <textarea class="form-control mt-2 catatan-source" rows="2" placeholder="Catatan approval (opsional)..."></textarea>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="card shadow-sm">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-clipboard-check fa-2x mb-3 opacity-25 d-block"></i>
            Tidak ada demob yang menunggu approval.
        </div>
    </div>
@endforelse

@push('scripts')
<script>
    // Salin catatan ke form yang disubmit.
    document.querySelectorAll('.card .card-body').forEach(function (block) {
        var source = block.querySelector('.catatan-source');
        if (!source) return;
        block.querySelectorAll('.form-approval').forEach(function (form) {
            form.addEventListener('submit', function () {
                var mirror = form.querySelector('.catatan-mirror');
                if (mirror) mirror.value = source.value;
            });
        });
    });
</script>
@endpush

@endsection
