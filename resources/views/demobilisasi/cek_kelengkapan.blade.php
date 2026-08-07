@extends('layouts.kai')

@section('page_title', 'Pengecekan Kelengkapan — ' . $nama)

@section('content')

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

<form action="{{ route('gudang.demobilisasi.cek.store', [$idgudang, $mobilisasi->id, $mp->id]) }}" method="POST" id="formCek">
    @csrf

    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="card-title mb-0">Pengecekan PPE (Personal Protective Equipment)</h5>
            <small class="text-muted">Hanya item Non Consumable.</small>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nama PPE</th>
                        <th class="text-center" style="width:90px">Jumlah</th>
                        <th style="width:180px">Status Kondisi</th>
                        <th class="text-center" style="width:150px">Jumlah Bermasalah</th>
                        <th style="width:30%">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        @php $bermasalah = in_array($item['kondisi'], ['tidak_layak', 'hilang'], true); @endphp
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $item['label'] }}</td>
                            <td class="text-center">{{ $item['jumlah'] }}</td>
                            <td>
                                <select name="kondisi[{{ $item['idsubbarang'] }}]"
                                    class="form-select form-select-sm kondisi-select" {{ $readonly ? 'disabled' : 'required' }}>
                                    <option value="" {{ $item['kondisi'] ? '' : 'selected' }} disabled>Pilih Status</option>
                                    <option value="layak" {{ $item['kondisi'] === 'layak' ? 'selected' : '' }}>Layak</option>
                                    <option value="tidak_layak" {{ $item['kondisi'] === 'tidak_layak' ? 'selected' : '' }}>Tidak Layak</option>
                                    <option value="hilang" {{ $item['kondisi'] === 'hilang' ? 'selected' : '' }}>Hilang</option>
                                </select>
                            </td>
                            <td class="text-center">
                                <input type="number" name="qty_bermasalah[{{ $item['idsubbarang'] }}]"
                                    class="form-control form-control-sm text-center qty-bermasalah-input"
                                    min="1" max="{{ $item['jumlah'] }}"
                                    value="{{ $bermasalah ? ($item['qty_bermasalah'] ?? $item['jumlah']) : '' }}"
                                    {{ $readonly || ! $bermasalah ? 'disabled' : '' }}>
                                <small class="text-muted d-block mt-1">dari {{ $item['jumlah'] }} unit, sisanya dianggap Layak</small>
                            </td>
                            <td>
                                <input type="text" name="catatan[{{ $item['idsubbarang'] }}]"
                                    class="form-control form-control-sm catatan-input"
                                    value="{{ $item['catatan'] }}" {{ $readonly ? 'disabled' : '' }}
                                    placeholder="Catatan (wajib untuk status Tidak Layak / Hilang)">
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada item Non Consumable untuk dicek.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @unless($readonly)
            @if($items->isNotEmpty())
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary px-4">Submit Pengecekan</button>
                </div>
            @endif
        @else
            <div class="card-footer text-muted small">
                Pengecekan sudah dikirim dan tidak bisa diubah.
            </div>
        @endunless
    </div>
</form>

@push('scripts')
<script>
    // Input jumlah bermasalah hanya aktif untuk kondisi Tidak Layak / Hilang.
    document.querySelectorAll('.kondisi-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            var row = this.closest('tr');
            var qtyInput = row.querySelector('.qty-bermasalah-input');
            if (!qtyInput) return;

            var bermasalah = this.value === 'tidak_layak' || this.value === 'hilang';
            qtyInput.disabled = !bermasalah;
            if (bermasalah && qtyInput.value === '') {
                qtyInput.value = qtyInput.max;
            }
            if (!bermasalah) {
                qtyInput.value = '';
            }
        });
    });

    document.getElementById('formCek')?.addEventListener('submit', function (e) {
        var rows = document.querySelectorAll('tbody tr');
        for (var i = 0; i < rows.length; i++) {
            var sel = rows[i].querySelector('.kondisi-select');
            var note = rows[i].querySelector('.catatan-input');
            var qtyInput = rows[i].querySelector('.qty-bermasalah-input');
            if (!sel) continue;
            if (sel.value === 'tidak_layak' || sel.value === 'hilang') {
                if (note && note.value.trim() === '') {
                    e.preventDefault();
                    alert('Catatan wajib diisi untuk item dengan kondisi Tidak Layak / Hilang.');
                    note.focus();
                    return;
                }
                if (qtyInput && !qtyInput.disabled) {
                    var qty = parseInt(qtyInput.value, 10) || 0;
                    var max = parseInt(qtyInput.max, 10) || 1;
                    if (qty < 1 || qty > max) {
                        e.preventDefault();
                        alert('Jumlah bermasalah harus antara 1 dan ' + max + '.');
                        qtyInput.focus();
                        return;
                    }
                }
            }
        }
    });
</script>
@endpush

@endsection
