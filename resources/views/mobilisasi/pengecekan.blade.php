@extends('layouts.kai')

@section('page_title', 'Pengecekan — ' . $nama)

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
        <div class="mt-2 small">
            <a href="{{ route('gudang.stok', $idgudang) }}" class="alert-link me-3">Tambah Stok</a>
            <a href="{{ route('gudang.permintaan', $idgudang) }}" class="alert-link">Buat MR</a>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('gudang.mobilisasi.show', [$idgudang, $mobilisasi->id]) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
    <div>
        <span class="me-2">Personel: <strong>{{ $nama }}</strong></span>
        @if($mp->submitted_at)
            <span class="badge bg-primary">Sudah di-submit</span>
        @elseif($lengkap)
            <span class="badge bg-success">Lengkap</span>
        @else
            <span class="badge bg-warning text-dark">Tidak Lengkap</span>
        @endif
    </div>
</div>

<p class="text-muted small mb-3">
    Kebutuhan posisi PPE berdasarkan <strong>Sub Barang</strong>. Jika barang punya lebih dari satu varian, pilih varian spesifik yang dikeluarkan dari stok.
</p>

@php
    $sections = [
        ['Pengecekan PPE (Personal Protective Equipment)', $itemsPpe, 'Status PPE'],
        ['Pengecekan Consumable', $itemsConsumable, 'Status'],
    ];
@endphp

@foreach($sections as [$judul, $items, $statusLabel])
    <div class="card shadow-sm mb-3">
        <div class="card-header"><h5 class="card-title mb-0">{{ $judul }}</h5></div>
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Nama PPE (Sub Barang)</th>
                        <th class="text-center" style="width:100px">Jumlah</th>
                        <th class="text-center" style="width:140px">Varian / Stok</th>
                        <th class="text-center" style="width:200px">{{ $statusLabel }}</th>
                        <th style="width:22%">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $item['label'] }}</td>
                            <td class="text-center">{{ $item['jumlah'] }}</td>
                            <td class="text-center">
                                @if($item['status'] === 'ada' && !empty($item['varian_label']))
                                    <small class="text-muted d-block">{{ $item['varian_label'] }}</small>
                                @elseif($item['status'] === 'ada' || ($item['from_keluar'] ?? false))
                                    <span class="text-muted">-</span>
                                @elseif(($item['issue_qty'] ?? 0) <= 0)
                                    <span class="text-muted">-</span>
                                @elseif(empty($item['varian_options']))
                                    <span class="badge bg-danger">Tidak ada varian</span>
                                @elseif(! ($item['stok_in_table'] ?? false))
                                    <span class="badge bg-danger">Belum di stok</span>
                                @elseif(! ($item['stok_ok'] ?? false))
                                    <span class="badge bg-warning text-dark">Stok kurang</span>
                                @elseif(count($item['varian_options']) === 1)
                                    <small class="text-muted d-block">{{ $item['varian_options'][0]['label'] }}</small>
                                    <span class="badge bg-success">Stok: {{ $item['varian_options'][0]['stok'] }}</span>
                                @else
                                    <span class="badge bg-success">{{ count($item['varian_options']) }} varian</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($item['status'] === 'ada')
                                    @if($item['from_keluar'] ?? false)
                                        <span class="btn btn-sm btn-success px-3 disabled">Ada</span>
                                        <small class="d-block text-muted mt-1">Sudah punya</small>
                                    @else
                                        <form action="{{ route('gudang.mobilisasi.pengecekan.update', [$idgudang, $mobilisasi->id, $mp->id]) }}"
                                            method="POST" class="d-inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="idsubbarang" value="{{ $item['idsubbarang'] }}">
                                            <input type="hidden" name="action" value="tidak">
                                            <button type="submit" {{ $mp->submitted_at ? 'disabled' : '' }}
                                                class="btn btn-sm btn-success px-3">Ada</button>
                                        </form>
                                        @if(!empty($item['varian_label']))
                                            <small class="d-block text-muted mt-1">{{ $item['varian_label'] }}</small>
                                        @endif
                                    @endif
                                @elseif(($item['issue_qty'] ?? 0) <= 0)
                                    <form action="{{ route('gudang.mobilisasi.pengecekan.update', [$idgudang, $mobilisasi->id, $mp->id]) }}"
                                        method="POST" class="d-inline">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="idsubbarang" value="{{ $item['idsubbarang'] }}">
                                        <input type="hidden" name="action" value="ada">
                                        <button type="submit" {{ $mp->submitted_at ? 'disabled' : '' }}
                                            class="btn btn-sm btn-outline-success px-3">Tandai Ada</button>
                                    </form>
                                @elseif(! ($item['stok_ok'] ?? true))
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary px-3" disabled>Tambahkan +</button>
                                        <div class="mt-1">
                                            <a href="{{ route('gudang.stok', $idgudang) }}" class="badge bg-light text-dark border text-decoration-none">+ Stok</a>
                                            <a href="{{ route('gudang.permintaan', $idgudang) }}" class="badge bg-light text-dark border text-decoration-none">Buat MR</a>
                                        </div>
                                    </div>
                                @else
                                    @if(count($item['varian_options']) === 1)
                                        <form action="{{ route('gudang.mobilisasi.pengecekan.update', [$idgudang, $mobilisasi->id, $mp->id]) }}"
                                            method="POST" class="d-inline">
                                            @csrf @method('PUT')
                                            <input type="hidden" name="idsubbarang" value="{{ $item['idsubbarang'] }}">
                                            <input type="hidden" name="action" value="ada">
                                            <input type="hidden" name="idbarangvarian" value="{{ $item['varian_options'][0]['idvarian'] }}">
                                            <button type="submit" {{ $mp->submitted_at ? 'disabled' : '' }}
                                                class="btn btn-sm btn-outline-success px-3">Tambahkan +</button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-sm btn-outline-success px-3 btn-pilih-varian"
                                            {{ $mp->submitted_at ? 'disabled' : '' }}
                                            data-bs-toggle="modal" data-bs-target="#modalPilihVarian"
                                            data-idsubbarang="{{ $item['idsubbarang'] }}"
                                            data-label="{{ $item['label'] }}"
                                            data-issue-qty="{{ $item['issue_qty'] }}"
                                            data-varian-options='@json($item['varian_options'])'>
                                            Tambahkan +
                                        </button>
                                    @endif
                                @endif
                            </td>
                            <td>{{ $item['catatan'] ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada item.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach

{{-- Modal pilih varian --}}
<div class="modal fade" id="modalPilihVarian" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('gudang.mobilisasi.pengecekan.update', [$idgudang, $mobilisasi->id, $mp->id]) }}" method="POST">
                @csrf @method('PUT')
                <input type="hidden" name="idsubbarang" id="modalIdSubBarang">
                <input type="hidden" name="action" value="ada">
                <div class="modal-header">
                    <h5 class="modal-title">Pilih Varian Barang</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-1 text-muted small">Sub Barang</p>
                    <p class="fw-bold" id="modalSubLabel">-</p>
                    <p class="mb-3 small">Kebutuhan: <strong id="modalIssueQty">-</strong> unit</p>

                    <label class="form-label fw-semibold">Varian yang dikeluarkan <span class="text-danger">*</span></label>
                    <select name="idbarangvarian" id="modalVarianSelect" class="form-select" required>
                        <option value="" disabled selected>— Pilih Varian —</option>
                    </select>
                    <div id="modalVarianHint" class="form-text mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="modalBtnSubmit">Keluarkan & Tandai Ada</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if(! $mp->submitted_at)
    <div class="d-flex justify-content-end gap-2">
        <form action="{{ route('gudang.mobilisasi.pengecekan.submit', [$idgudang, $mobilisasi->id, $mp->id]) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary px-4" {{ $lengkap ? '' : 'disabled' }}>
                Submit Pengecekan
            </button>
        </form>
    </div>
    @unless($lengkap)
        <p class="text-end text-muted small mt-2">
            Semua status harus "Ada". Pilih varian yang stoknya cukup, atau tambah stok / buat MR dulu.
        </p>
    @endunless
@endif

@push('scripts')
<script>
    document.querySelectorAll('.btn-pilih-varian').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var idsub = this.dataset.idsubbarang;
            var label = this.dataset.label;
            var issueQty = parseInt(this.dataset.issueQty, 10);
            var options = JSON.parse(this.dataset.varianOptions || '[]');

            document.getElementById('modalIdSubBarang').value = idsub;
            document.getElementById('modalSubLabel').textContent = label;
            document.getElementById('modalIssueQty').textContent = issueQty;

            var select = document.getElementById('modalVarianSelect');
            select.innerHTML = '<option value="" disabled selected>— Pilih Varian —</option>';

            var hasSelectable = false;
            options.forEach(function (opt) {
                var ok = opt.stok >= issueQty;
                var el = document.createElement('option');
                el.value = opt.idvarian;
                el.textContent = opt.label + ' — Stok: ' + opt.stok + (ok ? '' : ' (kurang)');
                el.disabled = !ok;
                if (ok) hasSelectable = true;
                select.appendChild(el);
            });

            var hint = document.getElementById('modalVarianHint');
            var submitBtn = document.getElementById('modalBtnSubmit');

            if (!hasSelectable) {
                hint.innerHTML = 'Tidak ada varian dengan stok cukup. '
                    + '<a href="{{ route('gudang.stok', $idgudang) }}">Tambah Stok</a> atau '
                    + '<a href="{{ route('gudang.permintaan', $idgudang) }}">Buat MR</a>.';
                submitBtn.disabled = true;
            } else {
                hint.textContent = 'Hanya varian dengan stok ≥ kebutuhan yang bisa dipilih.';
                submitBtn.disabled = false;
            }
        });
    });
</script>
@endpush

@endsection
