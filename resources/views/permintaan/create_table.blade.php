@extends('layouts.kai')

@section('page_title', 'Buat Tabel Permintaan — ' . ($gudang['namagudang'] ?? 'Gudang'))

@section('content')
<style>
    .permintaan-item-panel {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.75rem 0.75rem 0.25rem;
        background: #fff;
    }
    .permintaan-item-panel .dataTables_wrapper .dataTables_filter {
        float: right;
        margin-bottom: 0.75rem;
    }
    .permintaan-item-panel .dataTables_wrapper .dataTables_filter input {
        min-width: 220px;
    }
    .permintaan-item-panel .dataTables_scrollBody {
        max-height: 28rem;
    }
    .permintaan-item-panel table.dataTable thead th {
        white-space: nowrap;
    }
</style>

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
                <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-3">
                    <div style="min-width: 220px; max-width: 280px;">
                        <label class="form-label fw-semibold mb-1">Tanggal Permintaan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_permintaan" class="form-control"
                            value="{{ old('tanggal_permintaan', now()->toDateString()) }}" required>
                    </div>
                    <button type="button" class="btn btn-sm btn-success" id="btnPilihSemua">Pilih Semua</button>
                </div>

                <div class="permintaan-item-panel mb-3">
                    <table id="tabelBuatPermintaan" class="table table-bordered align-middle mb-0 w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Nama Barang</th>
                                <th class="text-center" style="width:120px">Stok Saat Ini</th>
                                <th class="text-center" style="width:120px">Input Stok</th>
                                <th class="text-center" style="width:90px">Pilih</th>
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

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fas fa-file-excel me-1"></i> Submit & Download Excel
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        var table = $('#tabelBuatPermintaan').DataTable({
            order: [[0, 'asc']],
            paging: false,
            info: true,
            scrollY: '28rem',
            scrollCollapse: true,
            autoWidth: false,
            language: {
                search: 'Cari:',
                info: '_TOTAL_ barang',
                infoEmpty: 'Tidak ada barang',
                infoFiltered: '(dari _MAX_ barang)',
                emptyTable: 'Belum ada data.',
                zeroRecords: 'Barang tidak ditemukan.',
            },
            columnDefs: [
                { orderable: false, searchable: false, targets: [2, 3] },
            ],
        });

        $('#tabelBuatPermintaan').on('change', '.chk-item', function () {
            var idx = this.dataset.index;
            var input = $(this).closest('tr').find('.input-qty').get(0)
                || document.querySelector('.input-qty[data-index="' + idx + '"]');
            if (!input) return;
            input.disabled = !this.checked;
            if (!this.checked) input.value = 0;
        });

        $('#btnPilihSemua').on('click', function () {
            var nodes = table.$('.chk-item');
            var allChecked = nodes.length > 0 && nodes.filter(':checked').length === nodes.length;
            nodes.each(function () {
                this.checked = !allChecked;
                $(this).trigger('change');
            });
            this.textContent = allChecked ? 'Pilih Semua' : 'Batal Pilih';
        });

        $('#formBuatPermintaan').on('submit', function (e) {
            var container = document.getElementById('itemsContainer');
            container.innerHTML = '';
            var count = 0;

            table.$('.chk-item').filter(':checked').each(function () {
                var idx = this.dataset.index;
                var qtyInput = table.$('.input-qty[data-index="' + idx + '"]').get(0);
                var qty = parseInt(qtyInput ? qtyInput.value : '0', 10) || 0;
                if (qty < 1) return;

                var idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'items[' + count + '][stok_id]';
                idInput.value = this.dataset.stokId;
                container.appendChild(idInput);

                var hiddenQty = document.createElement('input');
                hiddenQty.type = 'hidden';
                hiddenQty.name = 'items[' + count + '][qty]';
                hiddenQty.value = qty;
                container.appendChild(hiddenQty);

                count++;
            });

            if (count === 0) {
                e.preventDefault();
                alert('Pilih minimal 1 barang dengan qty lebih dari 0.');
            }
        });
    });
</script>
@endpush

@endsection
