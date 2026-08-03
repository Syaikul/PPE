@extends('layouts.kai')

@section('page_title', 'Peminjaman PPE — ' . ($gudang['namagudang'] ?? 'Gudang'))

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
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalAjukanPinjaman"
        {{ $stokList->isEmpty() ? 'disabled' : '' }}>
        <i class="fas fa-plus me-1"></i> Ajukan Pinjaman
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title mb-0">Peminjaman PPE</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelPeminjaman" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>Nama Barang</th>
                        <th class="text-center">Qty</th>
                        <th>Tanggal</th>
                        <th>Dari</th>
                        <th>Untuk</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($peminjamanList as $row)
                        @php
                            $namaBarang = \App\Services\PeminjamanPpeService::labelForItem(
                                $row->idsubbarang,
                                $row->idbarangvarian,
                                $subBarangMap,
                                $varianMap
                            );
                            $dari = $gudangMap[$row->idgudang_sumber]['namagudang'] ?? 'Gudang #'.$row->idgudang_sumber;
                            $untuk = $gudangMap[$row->idgudang_peminjam]['namagudang'] ?? 'Gudang #'.$row->idgudang_peminjam;
                            $isSumber = (int) $row->idgudang_sumber === (int) $idgudang;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $namaBarang }}</td>
                            <td class="text-center">{{ $row->qty }}</td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    @foreach($row->tanggalDisplayLines() as $line)
                                        <small>{{ $line }}</small>
                                    @endforeach
                                </div>
                            </td>
                            <td>{{ $dari }}</td>
                            <td>{{ $untuk }}</td>
                            <td style="text-align:left !important;">
                                @if($row->isPending())
                                    <div style="display:flex; flex-direction:column; align-items:flex-start; gap:4px;">
                                        <span class="badge bg-warning text-dark">{{ $row->statusLabel() }}</span>
                                    </div>
                                @elseif($row->isRejected())
                                    <div style="display:flex; flex-direction:column; align-items:flex-start; gap:4px;">
                                        <span class="badge bg-danger">Not Approve</span>
                                        <span class="badge bg-light text-dark border" style="text-align:left;">Note:{{ $row->catatan_tolak }}</span>
                                    </div>
                                @else
                                    <div style="display:flex; flex-direction:column; align-items:flex-start; gap:4px;">
                                        <span class="badge bg-success">{{ $row->statusLabel() }}</span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if($isSumber && $row->isPending())
                                    <form action="{{ route('gudang.peminjaman-ppe.approve', [$idgudang, $row->id]) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Terima pengajuan pinjaman ini? Stok akan dipindahkan ke gudang peminjam.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">Terima</button>
                                    </form>
                                    <button type="button" class="btn btn-sm btn-danger btn-tolak"
                                        data-id="{{ $row->id }}"
                                        data-bs-toggle="modal" data-bs-target="#modalTolakPinjaman">
                                        Tolak
                                    </button>
                                @elseif($isSumber && $row->isApproved())
                                    <form action="{{ route('gudang.peminjaman-ppe.kembalikan', [$idgudang, $row->id]) }}"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Yakin barang sudah dikembalikan dan sudah diperiksa? Stok akan dikembalikan ke gudang sumber.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Kembalikan Barang</button>
                                    </form>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Ajukan Pinjaman --}}
<div class="modal fade" id="modalAjukanPinjaman" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajukan Pinjaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('gudang.peminjaman-ppe.store', $idgudang) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">PPE yang Ingin Dipinjam <span class="text-danger">*</span></label>
                        <select name="stok_id" class="form-select" required>
                            <option value="" disabled selected>— Pilih Barang —</option>
                            @foreach($stokList as $stok)
                                @php $label = \App\Services\StokItemService::labelForRow($stok, $subBarangMap, $varianMap); @endphp
                                <option value="{{ $stok->id }}" {{ old('stok_id') == $stok->id ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Barang harus sudah terdaftar di stok gudang Anda.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Qty <span class="text-danger">*</span></label>
                        <input type="number" name="qty" class="form-control" min="1" value="{{ old('qty', 1) }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Sumber Peminjaman <span class="text-danger">*</span></label>
                        <select name="idgudang_sumber" class="form-select" required>
                            <option value="" disabled selected>— Pilih Gudang Sumber —</option>
                            @foreach($gudangSumberList as $g)
                                <option value="{{ $g['idgudang'] }}" {{ old('idgudang_sumber') == $g['idgudang'] ? 'selected' : '' }}>
                                    {{ $g['namagudang'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="3" placeholder="Catatan pengajuan (opsional)">{{ old('catatan') }}</textarea>
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

{{-- Modal Tolak --}}
<div class="modal fade" id="modalTolakPinjaman" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tolak Peminjaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formTolakPinjaman" action="" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Catatan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="catatan_tolak" class="form-control" rows="3"
                            placeholder="Contoh: Mintanya kebanyakan" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        $('#tabelPeminjaman').DataTable({
            order: [[2, 'desc']],
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                emptyTable: 'Belum ada data peminjaman.',
                zeroRecords: 'Data tidak ditemukan.',
            },
            columnDefs: [{ orderable: false, targets: 6 }]
        });
    });

    document.querySelectorAll('.btn-tolak').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('formTolakPinjaman').action =
                '/gudang/{{ $idgudang }}/peminjaman-ppe/' + this.dataset.id + '/tolak';
        });
    });
</script>
@endpush

@endsection
