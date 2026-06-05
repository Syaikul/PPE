@extends('layouts.kai')

@section('page_title', 'Stok — ' . ($gudang['namagudang'] ?? 'Gudang'))

@section('content')

{{-- Alert success --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Header: breadcrumb + tombol tambah --}}
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
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahStok">
        Tambah Stok
    </button>
</div>

{{-- Card DataTable --}}
<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title">Data Stok</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelStok" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <!-- <th>ID</th> -->
                        <th>Kode Barang</th>
                        <th>Barang — Varian</th>
                                             <!-- <th>ID Gudang</th> -->
                        <th>Qty</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($stokList as $stok)
                        <tr>
                            <!-- <td>{{ $stok->id }}</td> -->
                            <td>
                                <small class="text-muted">
                                    {{ $varianMap[$stok->idbarangvarian]['kode'] ?? '-' }}
                                </small>
                            </td>
                            <td>
                                @if($varianMap->has($stok->idbarangvarian))
                                    {{ $varianMap[$stok->idbarangvarian]['label'] }}
                                @else
                                    <span class="text-muted fst-italic">Varian #{{ $stok->idbarangvarian }}</span>
                                @endif
                            </td>
                            
                                <!-- <td>{{ $stok->idgudang }}</td> -->
                            <td>
                                <span class="badge bg-success">{{ $stok->qty }}</span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-ubah"
                                    data-id="{{ $stok->id }}"
                                    data-idbarangvarian="{{ $stok->idbarangvarian }}"
                                    data-qty="{{ $stok->qty }}"
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
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah Stok --}}
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
                        <label class="form-label fw-semibold">Barang — Varian</label>
                        <select name="idbarangvarian" class="form-select" required>
                            <option value="" disabled selected>— Pilih Barang —</option>
                            @foreach($varianOptions as $v)
                                <option value="{{ $v['idvarian'] }}">
                                    {{ $v['label'] }}{{ $v['kode'] ? ' ('.$v['kode'].')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if(empty($varianOptions))
                            <small class="text-danger d-block mt-1">Tidak ada barang varian tersedia dari API.</small>
                        @endif
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

{{-- Modal Ubah Stok --}}
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
                        <label class="form-label fw-semibold">Barang — Varian</label>
                        <select name="idbarangvarian" id="ubahIdVarian" class="form-select" required>
                            <option value="" disabled>— Pilih Barang —</option>
                            @foreach($varianOptions as $v)
                                <option value="{{ $v['idvarian'] }}">
                                    {{ $v['label'] }}{{ $v['kode'] ? ' ('.$v['kode'].')' : '' }}
                                </option>
                            @endforeach
                        </select>
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
<script>
    // Init DataTables
    $(document).ready(function () {
        $('#tabelStok').DataTable({
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                paginate: {
                    previous: 'Sebelumnya',
                    next: 'Selanjutnya',
                },
                emptyTable: 'Belum ada data stok.',
                zeroRecords: 'Data tidak ditemukan.',
            },
            columnDefs: [
                { orderable: false, targets: 5 }  // kolom Aksi tidak sortable
            ]
        });
    });

    // Isi data ke modal Ubah saat tombol Ubah diklik
    document.querySelectorAll('.btn-ubah').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id           = this.dataset.id;
            var idVarian     = this.dataset.idbarangvarian;
            var qty          = this.dataset.qty;

            document.getElementById('formUbah').action =
                '/gudang/{{ $idgudang }}/stok/' + id;
            document.getElementById('ubahIdVarian').value = idVarian;
            document.getElementById('ubahQty').value = qty;
        });
    });
</script>
@endpush

@endsection
