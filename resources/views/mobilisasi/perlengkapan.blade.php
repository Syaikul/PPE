@extends('layouts.kai')

@section('page_title', 'Data Perlengkapan Mobilisasi')

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
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Mobilisasi
    </a>
    <span class="fw-semibold">{{ $mobilisasi->sr ? 'SR: '.$mobilisasi->sr : 'Mobilisasi #'.$mobilisasi->id }}</span>
</div>

{{-- ============ PERLENGKAPAN PER POSISI (gambar 2) ============ --}}
@forelse($usedPosisi as $idposisi)
    @php $namaPosisi = $posisiMap[$idposisi]['namaposisi'] ?? 'Posisi #'.$idposisi; @endphp
    <div class="card shadow-sm mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="fas fa-hard-hat me-2"></i>{{ $namaPosisi }}</h5>
            <button type="button" class="btn btn-sm btn-success btn-tambah-item"
                data-idposisi="{{ $idposisi }}" data-posisi="{{ $namaPosisi }}"
                data-bs-toggle="modal" data-bs-target="#modalTambahItem">
                Tambahkan Item +
            </button>
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
                                <button class="btn btn-sm btn-warning btn-edit-qty"
                                    data-id="{{ $item->id }}" data-qty="{{ $item->qty }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEditQty">Edit Jumlah</button>
                                <form action="{{ route('gudang.mobilisasi.perlengkapan.destroy', [$idgudang, $mobilisasi->id, $item->id]) }}"
                                    method="POST" class="d-inline" onsubmit="return confirm('Hapus item ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus Perlengkapan dalam Projek ini</button>
                                </form>
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
            <button type="button" class="btn btn-sm btn-success btn-tambah-byrequest"
                data-bs-toggle="modal" data-bs-target="#modalByRequest">
                Tambahkan Item +
            </button>
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
                            <td class="fw-semibold text-muted">{{ $posisiMap[$item->idposisi]['namaposisi'] ?? 'Posisi #'.$item->idposisi }}</td>
                            <td class="text-center">{{ $item->qty }}</td>
                            <td class="text-end pe-3">
                                <button class="btn btn-sm btn-warning btn-edit-qty"
                                    data-id="{{ $item->id }}" data-qty="{{ $item->qty }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEditQty">Edit Jumlah</button>
                                <form action="{{ route('gudang.mobilisasi.perlengkapan.destroy', [$idgudang, $mobilisasi->id, $item->id]) }}"
                                    method="POST" class="d-inline" onsubmit="return confirm('Hapus item ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus Perlengkapan dalam Projek ini</button>
                                </form>
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
            <form action="{{ route('gudang.mobilisasi.perlengkapan.store', [$idgudang, $mobilisasi->id]) }}" method="POST">
                @csrf
                <input type="hidden" name="jenis" value="by_request">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Item By Request</h5>
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
                        <small class="text-muted">Kategori (Consumable / Non Consumable) mengikuti data Stok.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Yang Mengajukan (Posisi)</label>
                        <select name="idposisi" class="form-select" required>
                            <option value="" disabled selected>— Pilih Posisi —</option>
                            @foreach($usedPosisi as $idposisi)
                                <option value="{{ $idposisi }}">{{ $posisiMap[$idposisi]['namaposisi'] ?? 'Posisi #'.$idposisi }}</option>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('#modalTambahItem, #modalByRequest').on('shown.bs.modal', function () {
            $(this).find('.select-barang').select2({
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

        document.querySelectorAll('.btn-edit-qty').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('formEditQty').action =
                    '/gudang/{{ $idgudang }}/mobilisasi/{{ $mobilisasi->id }}/perlengkapan/' + this.dataset.id;
                document.getElementById('editQtyInput').value = this.dataset.qty;
            });
        });
    });
</script>
@endpush

@endsection
