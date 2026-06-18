@extends('layouts.kai')

@section('page_title', 'Buat Tabel Permintaan — ' . ($gudang['namagudang'] ?? 'Gudang'))

@section('content')

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-warehouse me-1"></i> Ganti Gudang
        </a>
        <span class="text-muted">/</span>
        <span class="fw-semibold">{{ $gudang['namagudang'] ?? 'Gudang #'.$idgudang }}</span>
    </div>
    <a href="{{ route('gudang.permintaan', $idgudang) }}" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-list me-1"></i> Data Permintaan
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title mb-0">Buat Tabel Permintaan PPE</h4>
        <small class="text-muted">Hanya barang yang sudah terdaftar di Stok gudang ini.</small>
    </div>
    <div class="card-body">
        @if($stokList->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-box-open fa-2x mb-3 d-block"></i>
                Belum ada barang di stok. <a href="{{ route('gudang.stok', $idgudang) }}">Tambah Stok</a> terlebih dahulu.
            </div>
        @else
            <form id="formBuatPermintaan" action="{{ route('gudang.permintaan-ppe.export', $idgudang) }}" method="POST">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nomor MR <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_mr" class="form-control"
                            placeholder="Contoh: 0158/MR/DIV.2/ONWJ/WM/I-25" required value="{{ old('nomor_mr') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Tanggal Permintaan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_permintaan" class="form-control"
                            value="{{ old('tanggal_permintaan', now()->toDateString()) }}" required>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Barang</th>
                                <th class="text-center" style="width:120px">Stok Saat Ini</th>
                                <th class="text-center" style="width:120px">Input Stok</th>
                                <th class="text-center" style="width:100px">
                                    <button type="button" class="btn btn-sm btn-success" id="btnPilihSemua">Pilih Semua</button>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                                @foreach($stokList as $i => $stok)
                                @php $label = \App\Services\StokItemService::labelForRow($stok, $subBarangMap, $varianMap); @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $label }}</td>
                                    <td class="text-center">{{ $stok->qty }}</td>
                                    <td class="text-center">
                                        <input type="number" class="form-control form-control-sm text-center input-qty"
                                            data-index="{{ $i }}" value="0" min="0" disabled>
                                    </td>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input chk-item"
                                            data-index="{{ $i }}"
                                            data-stok-id="{{ $stok->id }}">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div id="itemsContainer"></div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-file-pdf me-1"></i> Submit & Download PDF
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.chk-item').forEach(function (chk) {
        chk.addEventListener('change', function () {
            var idx = this.dataset.index;
            var input = document.querySelector('.input-qty[data-index="' + idx + '"]');
            input.disabled = !this.checked;
            if (!this.checked) input.value = 0;
        });
    });

    var btnPilihSemua = document.getElementById('btnPilihSemua');
    if (btnPilihSemua) {
        btnPilihSemua.addEventListener('click', function () {
            var allChecked = [...document.querySelectorAll('.chk-item')].every(function (c) { return c.checked; });
            document.querySelectorAll('.chk-item').forEach(function (chk) {
                chk.checked = !allChecked;
                chk.dispatchEvent(new Event('change'));
            });
            this.textContent = allChecked ? 'Pilih Semua' : 'Batal Pilih';
        });
    }

    var form = document.getElementById('formBuatPermintaan');
    if (form) {
        form.addEventListener('submit', function (e) {
            var container = document.getElementById('itemsContainer');
            container.innerHTML = '';
            var count = 0;

            document.querySelectorAll('.chk-item:checked').forEach(function (chk) {
                var idx = chk.dataset.index;
                var qty = parseInt(document.querySelector('.input-qty[data-index="' + idx + '"]').value) || 0;
                if (qty < 1) return;

                var idInput = document.createElement('input');
                idInput.type = 'hidden';
            idInput.name = 'items[' + count + '][stok_id]';
            idInput.value = chk.dataset.stokId;
                container.appendChild(idInput);

                var qtyInput = document.createElement('input');
                qtyInput.type = 'hidden';
                qtyInput.name = 'items[' + count + '][qty]';
                qtyInput.value = qty;
                container.appendChild(qtyInput);

                count++;
            });

            if (count === 0) {
                e.preventDefault();
                alert('Pilih minimal 1 barang dengan qty lebih dari 0.');
            }
        });
    }
</script>
@endpush

@endsection
