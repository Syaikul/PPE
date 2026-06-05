@extends('layouts.kai')

@section('page_title', 'Data Permintaan — ' . ($gudang['namagudang'] ?? 'Gudang'))

@section('content')

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
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
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalBuatMR"
        {{ $stokList->isEmpty() ? 'disabled' : '' }}>
        Buat MR
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title">Data Permintaan</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelPermintaan" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>No MR</th>
                        <th>Item</th>
                        <th>Tanggal Permintaan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permintaanList as $mr)
                        <tr>
                            <td class="fw-bold">{{ $mr->nomor_mr }}</td>
                            <td>
                                @foreach($mr->items as $item)
                                    <div>{{ $varianMap[$item->idbarangvarian]['label'] ?? 'Barang #'.$item->idbarangvarian }}</div>
                                @endforeach
                            </td>
                            <td class="fw-bold">{{ $mr->tanggal_permintaan->format('j M') }}</td>
                            <td>
                                @php $st = $mr->status; @endphp
                                <span class="badge px-3 py-2
                                    {{ $st === 'Sudah Selesai' ? 'bg-success' : ($st === 'Sebagian' ? 'bg-info' : 'bg-warning text-dark') }}">
                                    {{ $st === 'Belum Selesai' ? 'Belum Selesai' : $st }}
                                </span>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('gudang.permintaan.show', [$idgudang, $mr->id]) }}"
                                    class="btn btn-sm btn-success">Detail</a>
                                <button class="btn btn-sm btn-warning btn-edit-mr"
                                    data-id="{{ $mr->id }}"
                                    data-nomor="{{ $mr->nomor_mr }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEditMR">Edit Nomor MR</button>
                                <form action="{{ route('gudang.permintaan.destroy', [$idgudang, $mr->id]) }}"
                                    method="POST" class="d-inline" onsubmit="return confirm('Hapus MR ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus MR</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Buat MR --}}
<div class="modal fade" id="modalBuatMR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Buat Material Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formBuatMR" action="{{ route('gudang.permintaan.store', $idgudang) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor MR</label>
                        <input type="text" name="nomor_mr" class="form-control" placeholder="Contoh: 0158/MR/DIV.2/ONWJ/WM/I-25" required>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
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
                                    @php $label = $varianMap[$stok->idbarangvarian]['label'] ?? 'Barang #'.$stok->idbarangvarian; @endphp
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
                                                data-id="{{ $stok->idbarangvarian }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div id="itemsContainer"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan MR</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Nomor MR --}}
<div class="modal fade" id="modalEditMR" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Nomor MR</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditMR" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <label class="form-label fw-semibold">Nomor MR</label>
                    <input type="text" name="nomor_mr" id="editNomorMR" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        $('#tabelPermintaan').DataTable({
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                emptyTable: 'Belum ada data permintaan.',
                zeroRecords: 'Data tidak ditemukan.',
            },
            columnDefs: [{ orderable: false, targets: [1, 4] }]
        });
    });

    // Checkbox enable/disable qty input
    document.querySelectorAll('.chk-item').forEach(function (chk) {
        chk.addEventListener('change', function () {
            var idx = this.dataset.index;
            var input = document.querySelector('.input-qty[data-index="' + idx + '"]');
            input.disabled = !this.checked;
            if (!this.checked) input.value = 0;
        });
    });

    // Pilih Semua
    document.getElementById('btnPilihSemua').addEventListener('click', function () {
        var allChecked = [...document.querySelectorAll('.chk-item')].every(c => c.checked);
        document.querySelectorAll('.chk-item').forEach(function (chk) {
            chk.checked = !allChecked;
            chk.dispatchEvent(new Event('change'));
        });
        this.textContent = allChecked ? 'Pilih Semua' : 'Batal Pilih';
    });

    // Submit: build hidden inputs for selected items
    document.getElementById('formBuatMR').addEventListener('submit', function (e) {
        var container = document.getElementById('itemsContainer');
        container.innerHTML = '';
        var count = 0;

        document.querySelectorAll('.chk-item:checked').forEach(function (chk) {
            var idx = chk.dataset.index;
            var qty = parseInt(document.querySelector('.input-qty[data-index="' + idx + '"]').value) || 0;
            if (qty < 1) return;

            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'items[' + count + '][id]';
            idInput.value = chk.dataset.id;
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

    // Edit MR modal
    document.querySelectorAll('.btn-edit-mr').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('formEditMR').action =
                '/gudang/{{ $idgudang }}/permintaan/' + this.dataset.id;
            document.getElementById('editNomorMR').value = this.dataset.nomor;
        });
    });
</script>
@endpush

@endsection
