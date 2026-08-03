@extends('layouts.kai')

@section('page_title', 'Spare Barang — ' . ($gudang['namagudang'] ?? 'Gudang'))

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
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-warehouse me-1"></i> Ganti Gudang
        </a>
        <span class="text-muted">/</span>
        <span class="fw-semibold">{{ $gudang['namagudang'] ?? 'Gudang #'.$idgudang }}</span>
    </div>
    <a href="{{ route('gudang.stok', $idgudang) }}" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-boxes me-1"></i> Data Stok
    </a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header">
        <h4 class="card-title mb-0">Buat Spare Barang</h4>
        <small class="text-muted">Barang yang dijadikan spare akan langsung mengurangi stok gudang.</small>
    </div>
    <div class="card-body">
        @if($stokList->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="fas fa-box-open fa-2x mb-3 d-block"></i>
                Belum ada stok yang bisa dijadikan spare. <a href="{{ route('gudang.stok', $idgudang) }}">Tambah Stok</a> terlebih dahulu.
            </div>
        @elseif($personelList->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="fas fa-users fa-2x mb-3 d-block"></i>
                Belum ada personel di gudang ini. <a href="{{ route('gudang.personel', $idgudang) }}">Tambah Personel</a> terlebih dahulu.
            </div>
        @else
            <form id="formSpareBarang" action="{{ route('gudang.spare-barang.store', $idgudang) }}" method="POST">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">No SR <span class="text-danger">*</span></label>
                        <input type="text" name="no_sr" class="form-control" placeholder="Contoh: 22"
                            value="{{ old('no_sr') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control"
                            value="{{ old('tanggal', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Penanggung Jawab <span class="text-danger">*</span></label>
                        <select name="personel_id" class="form-select" required>
                            <option value="" disabled selected>— Pilih Personel —</option>
                            @foreach($personelList as $p)
                                <option value="{{ $p['id'] }}" {{ old('personel_id') == $p['id'] ? 'selected' : '' }}>
                                    {{ $p['nama'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-center" style="width:120px">Stok Saat Ini</th>
                                <th class="text-center" style="width:120px">Jumlah Spare</th>
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
                                            data-index="{{ $i }}"
                                            data-max="{{ $stok->qty }}"
                                            value="0" min="0" max="{{ $stok->qty }}" disabled>
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
                        <i class="fas fa-box me-1"></i> Simpan Spare Barang
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title mb-0">Data Spare Barang</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width:80px">SR</th>
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
                            $items = $sr->items;
                            $rowspan = max(1, $items->count());
                            $namaPj = $sr->personel
                                ? ($personelMapApi[$sr->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$sr->personel->idpersonel)
                                : '-';
                            $srReturned = $sr->isReturned();
                            $itemsTersedia = $items->filter(fn ($it) => ! $it->isReturned() && $it->sisa > 0);
                        @endphp
                        @foreach($items as $idx => $item)
                            @php
                                $labelItem = \App\Services\SpareBarangService::labelForItem(
                                    $item->idsubbarang,
                                    $item->idbarangvarian,
                                    $subBarangMap,
                                    $varianMap
                                );
                                $menunggu = $item->pemakaian
                                    ->where('status', \App\Models\SpareBarangPemakaian::STATUS_MENUNGGU)
                                    ->sum('qty');
                            @endphp
                            <tr>
                                @if($idx === 0)
                                    <td rowspan="{{ $rowspan }}" class="fw-bold">{{ $sr->no_sr }}</td>
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
                                            <form action="{{ route('gudang.spare-barang.kembalikan', [$idgudang, $sr->id]) }}"
                                                method="POST" class="d-inline"
                                                onsubmit="return confirm('Kembalikan sisa spare SR {{ $sr->no_sr }} ke stok gudang?')">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-primary">Kembalikan</button>
                                            </form>
                                            <button type="button" class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal" data-bs-target="#modalPakai{{ $sr->id }}"
                                                {{ $itemsTersedia->isEmpty() || $personelList->isEmpty() ? 'disabled' : '' }}>
                                                Dipakai
                                            </button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Belum ada data spare barang.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Dipakai per SR --}}
@foreach($srList as $sr)
    @php $itemsTersedia = $sr->items->filter(fn ($it) => ! $it->isReturned() && $it->sisa > 0); @endphp
    @if($itemsTersedia->isNotEmpty())
        <div class="modal fade" id="modalPakai{{ $sr->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Pakai Spare — SR {{ $sr->no_sr }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('gudang.spare-barang.pakai', [$idgudang, $sr->id]) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Item yang Dipakai <span class="text-danger">*</span></label>
                                <select name="spare_barang_item_id" class="form-select" required>
                                    <option value="" disabled selected>— Pilih Item —</option>
                                    @foreach($itemsTersedia as $item)
                                        @php
                                            $labelItem = \App\Services\SpareBarangService::labelForItem(
                                                $item->idsubbarang,
                                                $item->idbarangvarian,
                                                $subBarangMap,
                                                $varianMap
                                            );
                                        @endphp
                                        <option value="{{ $item->id }}">{{ $labelItem }} (sisa {{ $item->sisa }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Diberikan Kepada <span class="text-danger">*</span></label>
                                <select name="personel_id" class="form-select" required>
                                    <option value="" disabled selected>— Pilih Personel —</option>
                                    @foreach($personelList as $p)
                                        <option value="{{ $p['id'] }}">{{ $p['nama'] }}</option>
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

    var form = document.getElementById('formSpareBarang');
    if (form) {
        form.addEventListener('submit', function (e) {
            var container = document.getElementById('itemsContainer');
            container.innerHTML = '';
            var count = 0;
            var valid = true;

            document.querySelectorAll('.chk-item:checked').forEach(function (chk) {
                var idx = chk.dataset.index;
                var input = document.querySelector('.input-qty[data-index="' + idx + '"]');
                var qty = parseInt(input.value) || 0;
                var max = parseInt(input.dataset.max) || 0;

                if (qty < 1) return;

                if (qty > max) {
                    valid = false;
                    return;
                }

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
</script>
@endpush

@endsection
