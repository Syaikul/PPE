@extends('layouts.kai')

@section('page_title', 'Data Perlengkapan Mobilisasi')

@section('content')
<style>
    .spare-item-panel {
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 0.75rem 0.75rem 0.25rem;
        background: #fff;
    }
    .spare-item-panel .dataTables_wrapper .dataTables_filter {
        float: right;
        margin-bottom: 0.75rem;
    }
    .spare-item-panel .dataTables_wrapper .dataTables_filter input {
        min-width: 220px;
    }
    .spare-item-panel .dataTables_scrollBody {
        max-height: 28rem;
    }
    .spare-item-panel table.dataTable thead th {
        white-space: nowrap;
    }
</style>

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
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Mobilisasi
    </a>
    <span class="fw-semibold">{{ $mobilisasi->sr ? 'SR: '.$mobilisasi->sr : 'Mobilisasi #'.$mobilisasi->id }}</span>
</div>

@if($perlengkapanLocked)
    <div class="alert alert-info">
        Data perlengkapan hanya bisa dilihat. Pengecekan personel sudah disubmit, jadi item tidak bisa ditambah atau diubah.
    </div>
@endif

{{-- ============ PERLENGKAPAN PER POSISI (gambar 2) ============ --}}
@forelse($usedPosisi as $idposisi)
    @php $namaPosisi = $posisiMap[$idposisi]['namaposisi'] ?? 'Posisi #'.$idposisi; @endphp
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-hard-hat me-2"></i>{{ $namaPosisi }}</h5>
            @canCrud('mobilisasi')
            @if(! $perlengkapanLocked)
            <button type="button" class="btn btn-sm btn-success btn-tambah-item"
                data-idposisi="{{ $idposisi }}" data-posisi="{{ $namaPosisi }}"
                data-bs-toggle="modal" data-bs-target="#modalTambahItem">
                Tambahkan Item +
            </button>
            @endif
            @endcanCrud
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nama PPE</th>
                        <th class="text-center" style="width:180px">Kebutuhan Projek</th>
                        <th class="text-end pe-3" style="width:340px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($perlengkapanByPosisi->get($idposisi, collect()) as $item)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $subBarangMap[$item->idsubbarang]['label'] ?? 'Item #'.$item->idsubbarang }}</td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-end pe-3">
                                @if(! $perlengkapanLocked)
                                @canCrud('mobilisasi')
                                <button class="btn btn-sm btn-warning btn-edit-qty"
                                    data-id="{{ $item->id }}" data-qty="{{ $item->qty }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEditQty">Edit Jumlah</button>
                                <form action="{{ route('gudang.mobilisasi.perlengkapan.destroy', [$idgudang, $mobilisasi->id, $item->id]) }}"
                                    method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus Perlengkapan dalam Projek ini</button>
                                </form>
                                @else
                                <span class="text-muted">-</span>
                                @endcanCrud
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Belum ada perlengkapan untuk posisi ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="alert alert-warning">Belum ada posisi yang dipakai pada mobilisasi ini.</div>
@endforelse

{{-- ============ BY REQUEST (gambar 3) ============ --}}
@foreach([['Consumable', $byRequestConsumable], ['Non Consumable', $byRequestNonConsumable]] as [$katLabel, $items])
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                <i class="fas fa-plus-square me-2"></i>By Request — {{ $katLabel }}
            </h5>
            @canCrud('mobilisasi')
            @if(! $perlengkapanLocked)
            <button type="button" class="btn btn-sm btn-success btn-tambah-byrequest"
                data-kategori="{{ $katLabel }}"
                data-bs-toggle="modal" data-bs-target="#modalByRequest">
                Tambahkan Item +
            </button>
            @endif
            @endcanCrud
        </div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nama PPE</th>
                        <th>Yang Mengajukan</th>
                        <th class="text-center" style="width:160px">Kebutuhan Projek</th>
                        <th class="text-end pe-3" style="width:340px">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $subBarangMap[$item->idsubbarang]['label'] ?? 'Item #'.$item->idsubbarang }}</td>
                            <td class="fw-semibold text-muted">
                                {{ $item->pengaju_label }}
                                @if($item->untuk_user)
                                    <span class="badge bg-info text-dark ms-1">Klien</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-end pe-3">
                                @if($item->untuk_user)
                                    <span class="badge bg-secondary">Sudah keluar stok — tercatat di PPE Keluar</span>
                                @elseif(! $perlengkapanLocked)
                                    @canCrud('mobilisasi')
                                    <button class="btn btn-sm btn-warning btn-edit-qty"
                                        data-id="{{ $item->id }}" data-qty="{{ $item->qty }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEditQty">Edit Jumlah</button>
                                    <form action="{{ route('gudang.mobilisasi.perlengkapan.destroy', [$idgudang, $mobilisasi->id, $item->id]) }}"
                                        method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus Perlengkapan dalam Projek ini</button>
                                    </form>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endcanCrud
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">Belum ada item {{ $katLabel }}.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach

{{-- ============ SPARE BARANG (terikat mobilisasi ini) ============ --}}
<div class="card shadow-sm mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-0"><i class="fas fa-box-open me-2"></i>Spare Barang</h5>
            <small class="text-muted">Barang yang dijadikan spare langsung mengurangi stok gudang dan terikat ke mobilisasi ini. Saat demobilisasi, sisa dikembalikan ke stok dan yang terpakai tercatat di PPE Keluar.</small>
        </div>
        @if(! $perlengkapanLocked)
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#formSpareWrap"
            {{ $stokList->isEmpty() || $mobPersonelOptions->isEmpty() ? 'disabled' : '' }}>
            Buat Spare Barang +
        </button>
        @endif
    </div>

    {{-- Form buat SR spare --}}
    @if(! $perlengkapanLocked)
    <div class="collapse border-bottom" id="formSpareWrap">
        <div class="card-body">
            @if($stokList->isEmpty())
                <p class="text-muted mb-0">Belum ada stok yang bisa dijadikan spare.</p>
            @elseif($mobPersonelOptions->isEmpty())
                <p class="text-muted mb-0">Belum ada personel pada mobilisasi ini.</p>
            @else
                <form id="formSpareBarang" action="{{ route('gudang.mobilisasi.spare.store', [$idgudang, $mobilisasi->id]) }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">No SR</label>
                            <input type="text" class="form-control" value="{{ $mobilisasi->sr ?: '-' }}" readonly>
                            <small class="text-muted">Mengikuti SR mobilisasi.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tanggal</label>
                            <input type="date" class="form-control" value="{{ $mobilisasi->created_at?->toDateString() }}" readonly>
                            <small class="text-muted">Mengikuti tanggal mobilisasi.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Penanggung Jawab <span class="text-danger">*</span></label>
                            <select name="personel_id" class="form-select" required>
                                <option value="" disabled selected>— Pilih Personel MOB —</option>
                                @foreach($mobPersonelOptions as $opt)
                                    <option value="{{ $opt['personel_id'] }}" {{ old('personel_id') == $opt['personel_id'] ? 'selected' : '' }}>
                                        {{ $opt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Hanya personel yang ikut mobilisasi ini.</small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn btn-sm btn-success" id="btnPilihSemuaSpare">Pilih Semua</button>
                    </div>

                    <div class="spare-item-panel mb-3">
                        <table id="tabelSpareBarang" class="table table-bordered align-middle mb-0 w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama Barang</th>
                                    <th class="text-center" style="width:120px">Stok Saat Ini</th>
                                    <th class="text-center" style="width:120px">Jumlah Spare</th>
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
                                            <input type="number" class="form-control form-control-sm text-center spare-input-qty"
                                                data-index="{{ $i }}" data-max="{{ $stok->qty }}"
                                                value="0" min="0" max="{{ $stok->qty }}" disabled>
                                        </td>
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input spare-chk-item"
                                                data-index="{{ $i }}" data-stok-id="{{ $stok->id }}">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div id="spareItemsContainer"></div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        @canCrud('mobilisasi')
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-box me-1"></i> Simpan Spare Barang
                        </button>
                        @endcanCrud
                    </div>
                </form>
            @endif
        </div>
    </div>
    @endif
</div>

{{-- ============ MODALS ============ --}}


{{-- Tambah Item (perlengkapan per posisi) --}}
<div class="modal fade" id="modalTambahItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gudang.mobilisasi.perlengkapan.store', [$idgudang, $mobilisasi->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="jenis" value="perlengkapan">
                <input type="hidden" name="idposisi" id="tambahIdPosisi">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Item — <span id="tambahPosisiLabel"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama PPE</label>
                        <select name="idsubbarang" class="form-select select-barang" required>
                            <option value=""></option>
                            @foreach($subBarangOptions as $sb)
                                <option value="{{ $sb['idsubbarang'] }}">{{ $sb['label'] }}{{ $sb['kode'] ? ' ('.$sb['kode'].')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Kebutuhan Projek (Qty)</label>
                        <input type="number" name="qty" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- By Request --}}
<div class="modal fade" id="modalByRequest" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gudang.mobilisasi.perlengkapan.store', [$idgudang, $mobilisasi->id]) }}" method="POST" id="formByRequest">
                @csrf
                <input type="hidden" name="jenis" value="by_request">
                <input type="hidden" name="kategori" id="byRequestKategori" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Item By Request — <span id="byRequestKategoriLabel"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama PPE</label>
                        <select name="idsubbarang" class="form-select select-barang" id="byRequestSubBarang" required>
                            <option value=""></option>
                        </select>
                        <small class="text-muted">Hanya barang stok dengan kategori yang sama.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Yang Mengajukan</label>
                        <select name="penerima" class="form-select" id="byRequestPenerima" required>
                            <option value="" disabled selected>— Pilih Personel / User —</option>
                            @foreach($mobPersonelOptions as $opt)
                                <option value="{{ $opt['mp_id'] }}">{{ $opt['nama'] }}</option>
                            @endforeach
                            <option value="user">{{ $namaUserLabel }} — untuk klien</option>
                        </select>
                        <small class="text-muted">
                            Personel: barang keluar saat pengecekan personel tersebut.
                            User (klien): barang langsung keluar stok dan dianggap habis.
                        </small>
                    </div>
                    <div class="mb-3 d-none" id="byRequestVarianWrap">
                        <label class="form-label fw-semibold">Varian yang Dikeluarkan <span class="text-danger">*</span></label>
                        <select name="idbarangvarian" class="form-select" id="byRequestVarian">
                            <option value="" disabled selected>— Pilih Varian —</option>
                        </select>
                        <small class="text-muted">Wajib untuk request User karena barang langsung keluar stok.</small>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Kebutuhan Projek (Qty)</label>
                        <input type="number" name="qty" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Qty --}}
<div class="modal fade" id="modalEditQty" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditQty" action="" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Jumlah</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label fw-semibold">Kebutuhan Projek (Qty)</label>
                    <input type="number" name="qty" id="editQtyInput" class="form-control" min="1" required>
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
<script src="{{ asset('template') }}/assets/js/plugin/select2/select2.full.min.js"></script>
<script>
    $(document).ready(function () {
        $('#modalTambahItem, #modalByRequest').on('shown.bs.modal', function () {
            var $sel = $(this).find('.select-barang');
            if ($sel.hasClass('select2-hidden-accessible')) {
                $sel.select2('destroy');
            }
            $sel.select2({
                dropdownParent: $(this),
                placeholder: 'Ketik untuk cari PPE...',
                width: '100%',
            });
        });

        document.querySelectorAll('.btn-tambah-item').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('tambahIdPosisi').value = this.dataset.idposisi;
                document.getElementById('tambahPosisiLabel').textContent = this.dataset.posisi;
            });
        });

        var byRequestOptions = @json($byRequestOptionsByKategori);
        document.querySelectorAll('.btn-tambah-byrequest').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var kat = this.dataset.kategori;
                document.getElementById('byRequestKategori').value = kat;
                document.getElementById('byRequestKategoriLabel').textContent = kat;
                var select = document.getElementById('byRequestSubBarang');
                if ($(select).hasClass('select2-hidden-accessible')) {
                    $(select).select2('destroy');
                }
                select.innerHTML = '<option value=""></option>';
                var list = byRequestOptions[kat] || [];
                if (list.length === 0) {
                    var empty = document.createElement('option');
                    empty.value = '';
                    empty.disabled = true;
                    empty.textContent = 'Tidak ada item ' + kat + ' di stok';
                    select.appendChild(empty);
                } else {
                    list.forEach(function (sb) {
                        var el = document.createElement('option');
                        el.value = sb.idsubbarang;
                        el.textContent = sb.label + (sb.kode ? ' (' + sb.kode + ')' : '');
                        select.appendChild(el);
                    });
                }
            });
        });

        document.querySelectorAll('.btn-edit-qty').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('formEditQty').action =
                    '/gudang/{{ $idgudang }}/mobilisasi/{{ $mobilisasi->id }}/perlengkapan/' + this.dataset.id;
                document.getElementById('editQtyInput').value = this.dataset.qty;
            });
        });

        // ===== By Request: varian wajib dipilih bila penerima = User dan barang punya varian =====
        var varianBySubBarang = @json($varianBySubBarang);

        function refreshByRequestVarian() {
            var penerima = document.getElementById('byRequestPenerima').value;
            var idsub = $('#byRequestSubBarang').val();
            var wrap = document.getElementById('byRequestVarianWrap');
            var select = document.getElementById('byRequestVarian');
            var info = (idsub && varianBySubBarang[idsub]) ? varianBySubBarang[idsub] : null;
            var perluVarian = penerima === 'user' && info && info.has_variants;

            wrap.classList.toggle('d-none', !perluVarian);
            select.required = perluVarian;

            if (!perluVarian) {
                select.innerHTML = '<option value="" disabled selected>— Pilih Varian —</option>';
                return;
            }

            select.innerHTML = '<option value="" disabled selected>— Pilih Varian —</option>';
            info.varians.forEach(function (v) {
                var el = document.createElement('option');
                el.value = v.id;
                el.textContent = v.label;
                select.appendChild(el);
            });
        }

        document.getElementById('byRequestPenerima').addEventListener('change', refreshByRequestVarian);
        $('#byRequestSubBarang').on('change', refreshByRequestVarian);

        // ===== Spare Barang form (DataTables, sama pola Buat Tabel Permintaan) =====
        var spareTable = null;

        function initSpareTable() {
            if (spareTable || !document.getElementById('tabelSpareBarang')) return;

            spareTable = $('#tabelSpareBarang').DataTable({
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
        }

        $('#formSpareWrap').on('shown.bs.collapse', function () {
            initSpareTable();
            if (spareTable) {
                spareTable.columns.adjust();
            }
        });

        if ($('#formSpareWrap').hasClass('show')) {
            initSpareTable();
        }

        $('#tabelSpareBarang').on('change', '.spare-chk-item', function () {
            var idx = this.dataset.index;
            var input = $(this).closest('tr').find('.spare-input-qty').get(0);
            if (!input && spareTable) {
                input = spareTable.$('.spare-input-qty[data-index="' + idx + '"]').get(0);
            }
            if (!input) return;
            input.disabled = !this.checked;
            if (!this.checked) input.value = 0;
        });

        var btnPilihSemuaSpare = document.getElementById('btnPilihSemuaSpare');
        if (btnPilihSemuaSpare) {
            btnPilihSemuaSpare.addEventListener('click', function () {
                if (!spareTable) initSpareTable();
                if (!spareTable) return;

                var nodes = spareTable.$('.spare-chk-item');
                var allChecked = nodes.length > 0 && nodes.filter(':checked').length === nodes.length;
                nodes.each(function () {
                    this.checked = !allChecked;
                    $(this).trigger('change');
                });
                this.textContent = allChecked ? 'Pilih Semua' : 'Batal Pilih';
            });
        }

        var formSpare = document.getElementById('formSpareBarang');
        if (formSpare) {
            formSpare.addEventListener('submit', function (e) {
                if (!spareTable) initSpareTable();

                var container = document.getElementById('spareItemsContainer');
                container.innerHTML = '';
                var count = 0;
                var valid = true;
                var rows = spareTable ? spareTable.$('.spare-chk-item') : $('.spare-chk-item');

                rows.filter(':checked').each(function () {
                    var idx = this.dataset.index;
                    var input = spareTable
                        ? spareTable.$('.spare-input-qty[data-index="' + idx + '"]').get(0)
                        : document.querySelector('.spare-input-qty[data-index="' + idx + '"]');
                    var qty = parseInt(input ? input.value : '0', 10) || 0;
                    var max = parseInt(input && input.dataset.max ? input.dataset.max : '0', 10) || 0;

                    if (qty < 1) return;
                    if (qty > max) { valid = false; return; }

                    var idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'items[' + count + '][stok_id]';
                    idInput.value = this.dataset.stokId;
                    container.appendChild(idInput);

                    var qtyInput = document.createElement('input');
                    qtyInput.type = 'hidden';
                    qtyInput.name = 'items[' + count + '][qty]';
                    qtyInput.value = qty;
                    container.appendChild(qtyInput);

                    count++;
                });

                if (!valid) {
                    e.preventDefault();
                    alert('Jumlah spare tidak boleh melebihi stok tersedia.');
                    return;
                }

                if (count === 0) {
                    e.preventDefault();
                    alert('Pilih minimal 1 item dengan jumlah lebih dari 0.');
                }
            });
        }
    });
</script>
@endpush

@endsection
