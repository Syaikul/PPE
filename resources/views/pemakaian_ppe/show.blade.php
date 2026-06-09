@extends('layouts.kai')

@section('page_title', 'Pemakaian PPE — ' . $nama)

@section('content')

<div class="mb-3">
    <a href="{{ route('gudang.pemakaian-ppe', $idgudang) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

{{-- Profil --}}
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center mb-3">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                style="width:56px;height:56px;font-size:1.4rem;">
                {{ strtoupper(substr($nama, 0, 1)) }}
            </div>
            <div class="ms-3">
                <div class="fw-bold fs-4">{{ $nama }}</div>
                <div class="text-muted small">Data Profil Pengguna</div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label class="form-label text-muted small">POSISI</label>
                <input type="text" class="form-control" value="{{ $posisiLbl ?: '-' }}" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label text-muted small">PENGGUNAAN</label>
                <select class="form-select" id="itemSelect" {{ $itemHistories->isEmpty() ? 'disabled' : '' }}>
                    @forelse($itemHistories as $i => $item)
                        <option value="item-{{ $item['idsubbarang'] }}" {{ $i === 0 ? 'selected' : '' }}>{{ $item['label'] }}</option>
                    @empty
                        <option>Belum ada pemakaian</option>
                    @endforelse
                </select>
            </div>
        </div>
    </div>
</div>

{{-- Daftar Permintaan --}}
<div class="card shadow-sm">
    <div class="card-header border-start border-4 border-primary">
        <h4 class="card-title mb-0">Daftar Permintaan</h4>
    </div>
    <div class="card-body">
        @forelse($itemHistories as $i => $item)
            <div class="item-history" id="item-{{ $item['idsubbarang'] }}" style="{{ $i === 0 ? '' : 'display:none;' }}">
                @foreach($item['riwayat'] as $r)
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="text-primary fw-bold mb-2">Permintaan {{ $r['no'] }}</div>
                        <div class="row">
                            <div class="col-md-4"><span class="text-muted">Tanggal:</span> <strong>{{ $r['tanggal']->format('d M Y') }}</strong></div>
                            <div class="col-md-4"><span class="text-muted">Diambil dari Gudang:</span> <strong>{{ $r['gudang'] }}</strong></div>
                            <div class="col-md-4"><span class="text-muted">Catatan:</span> {{ $r['catatan'] ?: '-' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <p class="text-center text-muted py-4 mb-0">Belum ada riwayat pemakaian PPE Non Consumable.</p>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('itemSelect')?.addEventListener('change', function () {
        document.querySelectorAll('.item-history').forEach(function (el) { el.style.display = 'none'; });
        var target = document.getElementById(this.value);
        if (target) target.style.display = '';
    });
</script>
@endpush

@endsection
