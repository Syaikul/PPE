@extends('layouts.kai')

@section('page_title', 'Stok — ' . ($gudang['namagudang'] ?? 'Gudang'))

@section('content')

<style>
    .stok-info-warna-btn { color: #1572e8 !important; }
    .stok-info-warna-btn:hover { color: #0d5bbf !important; text-decoration: underline !important; }
    .stok-info-divider { height: 3px; background: #1572e8; border-radius: 2px; }
    .stok-info-pill {
        display: inline-block;
        width: 72px;
        height: 22px;
        border-radius: 999px;
    }
    .stok-info-table thead th {
        font-weight: 600;
        letter-spacing: 0.03em;
        border-bottom: 1px solid #eef1f4;
        padding-bottom: 0.75rem;
    }
    .stok-info-table tbody td {
        padding-top: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f1f3f5;
        vertical-align: middle;
    }
    .stok-info-table tbody tr:last-child td { border-bottom: 0; }
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
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-warehouse me-1"></i> Ganti Gudang
        </a>
        <span class="text-muted">/</span>
        <span class="fw-semibold">{{ $gudang['namagudang'] ?? 'Gudang #'.$idgudang }}</span>
        @if($gudang)
            <span class="badge bg-light text-secondary border">No. Kontrak: {{ $gudang['nomorkontrak'] }}</span>
        @endif
    </div>
    @canCrud('stok')
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahStok">
        Tambah Stok
    </button>
    @endcanCrud
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Data Stok</h4>
        <button type="button" class="btn btn-link p-0 text-decoration-none fw-semibold stok-info-warna-btn"
            data-bs-toggle="modal" data-bs-target="#modalInformasiWarna">
            Informasi Warna
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelStok" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th class="text-center">Qty</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stokList as $stok)
                        @php
                            $label = \App\Services\StokItemService::labelForRow($stok, $subBarangMap, $varianMap);
                            $kode = \App\Services\StokItemService::kodeForRow($stok, $subBarangMap, $varianMap);
                            $mm = $stokMetrics[$stok->id] ?? null;
                        @endphp
                        <tr>
                            <td><small class="text-muted">{{ $kode }}</small></td>
                            <td>
                                {{ $label }}
                                @if($stok->isSubLevel())
                                    <span class="badge bg-light text-muted border ms-1">Sub Barang</span>
                                @endif
                            </td>
                            <td>
                                @php $kat = $stok->kategori ?? 'Consumable'; @endphp
                                <span class="badge {{ $kat === 'Consumable' ? 'bg-info' : 'bg-secondary' }}">
                                    {{ $kat }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge fs-6" style="{{ $mm['badge']['style'] ?? 'background-color:#64748B;color:#fff' }}">
                                    {{ $stok->qty }}
                                </span>
                            </td>
                            <td>
                                @canCrud('stok')
                                <button class="btn btn-sm btn-warning btn-ubah"
                                    data-id="{{ $stok->id }}"
                                    data-label="{{ $label }}"
                                    data-qty="{{ $stok->qty }}"
                                    data-kategori="{{ $stok->kategori ?? 'Consumable' }}"
                                    data-persen="{{ $mm['persen'] ?? 10 }}"
                                    data-bs-toggle="modal" data-bs-target="#modalUbahStok">
                                    Ubah
                                </button>
                                <form action="{{ route('gudang.stok.destroy', [$idgudang, $stok->id]) }}"
                                    method="POST" class="d-inline"
                                    onsubmit="return confirm('Hapus stok ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endcanCrud
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInformasiWarna" tabindex="-1" aria-labelledby="labelInformasiWarna" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title w-100 text-center fw-bold" id="labelInformasiWarna">Informasi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="stok-info-divider mb-3"></div>
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0 stok-info-table">
                        <thead>
                            <tr class="text-muted text-uppercase small">
                                <th style="width:35%">Status</th>
                                <th>Penjelasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\App\Services\StokMinMaxService::colorLegend() as $item)
                                <tr>
                                    <td>
                                        <span class="stok-info-pill" style="background-color:{{ $item['color'] }}"></span>
                                    </td>
                                    <td class="text-muted">{{ $item['penjelasan'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahStok" tabindex="-1" aria-labelledby="labelTambahStok" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelTambahStok">Tambah Stok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('gudang.stok.store', $idgudang) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Barang</label>
                        <small class="text-muted d-block mb-1">
                            Sub barang jika tidak ada varian; per varian jika ada ukuran/warna. Hanya barang yang belum ada di stok.
                        </small>
                        <select name="stok_item" id="tambahBarangSelect" class="form-select" required>
                            <option value=""></option>
                            @foreach($stokOptionsTambah as $opt)
                                <option value="{{ $opt['key'] }}">
                                    {{ $opt['label'] }}{{ $opt['kode'] ? ' ('.$opt['kode'].')' : '' }}
                                    @if($opt['type'] === 'sub') — Sub Barang @endif
                                </option>
                            @endforeach
                        </select>
                        @if(empty($stokOptionsTambah))
                            <small class="text-muted d-block mt-1">Semua barang sudah terdaftar di stok. Tambah qty via MR atau tombol Ubah.</small>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="kategori" class="form-select" required>
                            <option value="Consumable" selected>Consumable</option>
                            <option value="Non Consumable">Non Consumable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Qty</label>
                        <input type="number" name="qty" class="form-control" placeholder="Masukkan jumlah" min="1" required>
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

<div class="modal fade" id="modalUbahStok" tabindex="-1" aria-labelledby="labelUbahStok" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="labelUbahStok">Ubah Stok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUbah" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Barang</label>
                        <input type="text" id="ubahLabel" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="kategori" id="ubahKategori" class="form-select" required>
                            <option value="Consumable">Consumable</option>
                            <option value="Non Consumable">Non Consumable</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Persen Min-Max (%)</label>
                        <input type="number" name="persen" id="ubahPersen" class="form-control" min="0" max="1000" step="0.1" required>
                        <small class="text-muted">Min = personel × persen ({{ $personelCount }} org). Max = {{ $personelCount }} (1 unit/orang).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Qty</label>
                        <input type="number" name="qty" id="ubahQty" class="form-control" min="1" required>
                    </div>
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
    function initBarangSelect(selector, modalId) {
        var $el = $(selector);
        if ($el.hasClass('select2-hidden-accessible')) {
            $el.select2('destroy');
        }
        $el.select2({
            dropdownParent: $(modalId),
            placeholder: 'Ketik keyword untuk cari barang...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: function () { return 'Barang tidak ditemukan'; },
                searching: function () { return 'Mencari...'; },
                inputTooShort: function () { return 'Ketik untuk mencari barang'; },
            },
        });
    }

    $(document).ready(function () {
        $('#modalTambahStok').on('shown.bs.modal', function () {
            initBarangSelect('#tambahBarangSelect', '#modalTambahStok');
        });

        $('#modalTambahStok').on('hidden.bs.modal', function () {
            $('#tambahBarangSelect').val(null).trigger('change');
        });

        $('#tabelStok').DataTable({
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                emptyTable: 'Belum ada data stok.',
                zeroRecords: 'Data tidak ditemukan.',
            },
            columnDefs: [{ orderable: false, targets: 4 }]
        });
    });

    document.querySelectorAll('.btn-ubah').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('formUbah').action =
                '/gudang/{{ $idgudang }}/stok/' + this.dataset.id;
            document.getElementById('ubahLabel').value = this.dataset.label;
            document.getElementById('ubahQty').value = this.dataset.qty;
            document.getElementById('ubahKategori').value = this.dataset.kategori;
            document.getElementById('ubahPersen').value = this.dataset.persen;
        });
    });
</script>
@endpush

@endsection
