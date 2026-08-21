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
            @canCrud('mobilisasi')
            <button type="button" class="btn btn-sm btn-success btn-tambah-item"
                data-idposisi="{{ $idposisi }}" data-posisi="{{ $namaPosisi }}"
                data-bs-toggle="modal" data-bs-target="#modalTambahItem">
                Tambahkan Item +
            </button>
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
                                @canCrud('mobilisasi')
                                <button class="btn btn-sm btn-warning btn-edit-qty"
                                    data-id="{{ $item->id }}" data-qty="{{ $item->qty }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEditQty">Edit Jumlah</button>
                                <form action="{{ route('gudang.mobilisasi.perlengkapan.destroy', [$idgudang, $mobilisasi->id, $item->id]) }}"
                                    method="POST" class="d-inline" onsubmit="return confirm('Hapus item ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus Perlengkapan dalam Projek ini</button>
                                </form>
                                @else
                                <span class="text-muted">-</span>
                                @endcanCrud
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
            <button type="button" class="btn btn-sm btn-success btn-tambah-byrequest"
                data-bs-toggle="modal" data-bs-target="#modalByRequest">
                Tambahkan Item +
            </button>
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
                                @else
                                    @canCrud('mobilisasi')
                                    <button class="btn btn-sm btn-warning btn-edit-qty"
                                        data-id="{{ $item->id }}" data-qty="{{ $item->qty }}"
                                        data-bs-toggle="modal" data-bs-target="#modalEditQty">Edit Jumlah</button>
                                    <form action="{{ route('gudang.mobilisasi.perlengkapan.destroy', [$idgudang, $mobilisasi->id, $item->id]) }}"
                                        method="POST" class="d-inline" onsubmit="return confirm('Hapus item ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus Perlengkapan dalam Projek ini</button>
                                    </form>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endcanCrud
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
            <small class="text-muted">Barang yang dijadikan spare langsung mengurangi stok gudang dan terikat ke mobilisasi ini.</small>
        </div>
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#formSpareWrap"
            {{ $stokList->isEmpty() || $mobPersonelOptions->isEmpty() ? 'disabled' : '' }}>
            Buat Spare Barang +
        </button>
    </div>

    {{-- Form buat SR spare --}}
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
                            <label class="form-label fw-semibold">No SR <span class="text-danger">*</span></label>
                            <input type="text" name="no_sr" class="form-control" placeholder="Contoh: 22"
                                value="{{ old('no_sr', $mobilisasi->sr) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control"
                                value="{{ old('tanggal', now()->toDateString()) }}" required>
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

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Item</th>
                                    <th class="text-center" style="width:120px">Stok Saat Ini</th>
                                    <th class="text-center" style="width:120px">Jumlah Spare</th>
                                    <th class="text-center" style="width:110px">
                                        <button type="button" class="btn btn-sm btn-success" id="btnPilihSemuaSpare">Pilih Semua</button>
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

    {{-- Daftar SR spare mobilisasi ini --}}
    <div class="card-body p-0">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-3" style="width:80px">SR</th>
                    <th>Item</th>
                    <th class="text-center" style="width:90px">Jumlah</th>
                    <th class="text-center" style="width:90px">Sisa</th>
                    <th style="width:180px">Penanggung Jawab</th>
                    <th style="width:200px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($srList as $sr)
                    @php
                        $spareItems = $sr->items;
                        $rowspan = max(1, $spareItems->count());
                        $pjOpt = $mobPersonelOptions->firstWhere('personel_id', $sr->personel_id);
                        $namaPj = $sr->personel ? ($pjOpt['nama'] ?? 'Personel #'.$sr->personel_id) : '-';
                        $srReturned = $sr->isReturned();
                        $itemsTersedia = $spareItems->filter(fn ($it) => ! $it->isReturned() && $it->sisa > 0);
                    @endphp
                    @foreach($spareItems as $idx => $item)
                        @php
                            $labelItem = \App\Services\SpareBarangService::labelForItem(
                                $item->idsubbarang, $item->idbarangvarian, $subBarangMap, $varianMap
                            );
                            $menunggu = $item->pemakaian
                                ->where('status', \App\Models\SpareBarangPemakaian::STATUS_MENUNGGU)
                                ->sum('qty');
                        @endphp
                        <tr>
                            @if($idx === 0)
                                <td rowspan="{{ $rowspan }}" class="ps-3 fw-bold">{{ $sr->no_sr }}</td>
                            @endif
                            <td>
                                {{ $labelItem }}
                                @if($item->isReturned())
                                    <span class="badge bg-secondary ms-1">Dikembalikan</span>
                                @endif
                                @if($menunggu > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $menunggu }} menunggu approval</span>
                                @endif
                            </td>
                            <td class="text-center">{{ $item->jumlah }}</td>
                            <td class="text-center">
                                <span class="badge {{ $item->sisa > 0 ? 'bg-success' : 'bg-secondary' }}">{{ $item->sisa }}</span>
                            </td>
                            @if($idx === 0)
                                <td rowspan="{{ $rowspan }}">{{ $namaPj }}</td>
                                <td rowspan="{{ $rowspan }}">
                                    @if($srReturned)
                                        <span class="text-muted">Sudah dikembalikan</span>
                                    @else
                                        <form action="{{ route('gudang.mobilisasi.spare.kembalikan', [$idgudang, $mobilisasi->id, $sr->id]) }}"
                                            method="POST" class="d-inline"
                                            onsubmit="return confirm('Kembalikan sisa spare SR {{ $sr->no_sr }} ke stok gudang?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-primary">Kembalikan</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-warning"
                                            data-bs-toggle="modal" data-bs-target="#modalPakaiSpare{{ $sr->id }}"
                                            {{ $itemsTersedia->isEmpty() || $mobPersonelOptions->isEmpty() ? 'disabled' : '' }}>
                                            Dipakai
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">Belum ada spare barang untuk mobilisasi ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- ============ MODALS ============ --}}

{{-- Modal Pakai Spare per SR --}}
@foreach($srList as $sr)
    @php $itemsTersedia = $sr->items->filter(fn ($it) => ! $it->isReturned() && $it->sisa > 0); @endphp
    @if($itemsTersedia->isNotEmpty())
        <div class="modal fade" id="modalPakaiSpare{{ $sr->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Pakai Spare — SR {{ $sr->no_sr }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('gudang.mobilisasi.spare.pakai', [$idgudang, $mobilisasi->id, $sr->id]) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Item yang Dipakai <span class="text-danger">*</span></label>
                                <select name="spare_barang_item_id" class="form-select" required>
                                    <option value="" disabled selected>— Pilih Item —</option>
                                    @foreach($itemsTersedia as $item)
                                        @php
                                            $labelItem = \App\Services\SpareBarangService::labelForItem(
                                                $item->idsubbarang, $item->idbarangvarian, $subBarangMap, $varianMap
                                            );
                                        @endphp
                                        <option value="{{ $item->id }}">{{ $labelItem }} (sisa {{ $item->sisa }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Diberikan Kepada <span class="text-danger">*</span></label>
                                <select name="personel_id" class="form-select" required>
                                    <option value="" disabled selected>— Pilih Personel MOB —</option>
                                    @foreach($mobPersonelOptions as $opt)
                                        <option value="{{ $opt['personel_id'] }}">{{ $opt['nama'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>
                                <input type="number" name="qty" class="form-control" min="1" value="1" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Catatan</label>
                                <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan pemakaian (opsional)"></textarea>
                            </div>
                            <div class="alert alert-light border small mb-0">
                                <i class="fas fa-info-circle me-1"></i>
                                Pengajuan ini akan masuk ke <strong>Approval Demob</strong>. Setelah disetujui, sisa spare
                                berkurang dan tercatat di <strong>PPE Keluar</strong>.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary">Ajukan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach

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
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Item By Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama PPE</label>
                        <select name="idsubbarang" class="form-select select-barang" id="byRequestSubBarang" required>
                            <option value=""></option>
                            @foreach($subBarangOptions as $sb)
                                <option value="{{ $sb['idsubbarang'] }}">{{ $sb['label'] }}{{ $sb['kode'] ? ' ('.$sb['kode'].')' : '' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Kategori (Consumable / Non Consumable) mengikuti data Stok.</small>
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

        // ===== Spare Barang form =====
        document.querySelectorAll('.spare-chk-item').forEach(function (chk) {
            chk.addEventListener('change', function () {
                var input = document.querySelector('.spare-input-qty[data-index="' + this.dataset.index + '"]');
                input.disabled = !this.checked;
                if (!this.checked) input.value = 0;
            });
        });

        var btnPilihSemuaSpare = document.getElementById('btnPilihSemuaSpare');
        if (btnPilihSemuaSpare) {
            btnPilihSemuaSpare.addEventListener('click', function () {
                var allChecked = [...document.querySelectorAll('.spare-chk-item')].every(function (c) { return c.checked; });
                document.querySelectorAll('.spare-chk-item').forEach(function (chk) {
                    chk.checked = !allChecked;
                    chk.dispatchEvent(new Event('change'));
                });
                this.textContent = allChecked ? 'Pilih Semua' : 'Batal Pilih';
            });
        }

        var formSpare = document.getElementById('formSpareBarang');
        if (formSpare) {
            formSpare.addEventListener('submit', function (e) {
                var container = document.getElementById('spareItemsContainer');
                container.innerHTML = '';
                var count = 0;
                var valid = true;

                document.querySelectorAll('.spare-chk-item:checked').forEach(function (chk) {
                    var input = document.querySelector('.spare-input-qty[data-index="' + chk.dataset.index + '"]');
                    var qty = parseInt(input.value) || 0;
                    var max = parseInt(input.dataset.max) || 0;

                    if (qty < 1) return;
                    if (qty > max) { valid = false; return; }

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
