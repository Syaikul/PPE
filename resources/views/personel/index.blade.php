@extends('layouts.kai')

@section('page_title', 'Data Personel — ' . ($gudang['namagudang'] ?? 'Gudang'))

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
        @if($gudang)
            <span class="badge bg-light text-secondary border">No. Kontrak: {{ $gudang['nomorkontrak'] }}</span>
        @endif
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahPersonel">
        Tambah Personel
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title">Data Personel</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelPersonel" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>Nama Personel</th>
                        <th>Posisi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($personelList as $personel)
                        @php
                            $nama = $personelMap[$personel->idpersonel]['namapersonel'] ?? 'Personel #'.$personel->idpersonel;
                            $posisiLabels = $personel->posisi
                                ->map(fn($p) => $posisiMap[$p->idposisi]['namaposisi'] ?? 'Posisi #'.$p->idposisi)
                                ->implode(', ');
                            $posisiIds = $personel->posisi->pluck('idposisi')->implode(',');
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $nama }}</td>
                            <td>{{ $posisiLabels ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $personel->status === 'Onshore' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $personel->status }}
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-warning btn-ubah"
                                    data-id="{{ $personel->id }}"
                                    data-status="{{ $personel->status }}"
                                    data-posisi="{{ $posisiIds }}"
                                    data-bs-toggle="modal" data-bs-target="#modalUbahPersonel">
                                    Ubah
                                </button>
                                <form action="{{ route('gudang.personel.destroy', [$idgudang, $personel->id]) }}"
                                    method="POST" class="d-inline"
                                    onsubmit="return confirm('Hapus personel ini?')">
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

{{-- Modal Tambah --}}
<div class="modal fade" id="modalTambahPersonel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Personel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('gudang.personel.store', $idgudang) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama Personel</label>
                        <select name="idpersonel" class="form-select" required>
                            <option value="" disabled selected>— Pilih Personel —</option>
                            @foreach($personelApiList as $p)
                                <option value="{{ $p['idpersonel'] }}">{{ $p['namapersonel'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Posisi <small class="text-muted">(bisa lebih dari 1)</small></label>
                        <div class="border rounded p-3" style="max-height:200px; overflow-y:auto;">
                            @foreach($posisiList as $pos)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="idposisi[]"
                                        value="{{ $pos['idposisi'] }}" id="tambah-pos-{{ $pos['idposisi'] }}">
                                    <label class="form-check-label" for="tambah-pos-{{ $pos['idposisi'] }}">
                                        {{ $pos['namaposisi'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="Offshore" selected>Offshore</option>
                            <option value="Onshore">Onshore</option>
                        </select>
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

{{-- Modal Ubah --}}
<div class="modal fade" id="modalUbahPersonel" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ubah Personel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formUbahPersonel" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Posisi <small class="text-muted">(bisa lebih dari 1)</small></label>
                        <div class="border rounded p-3" style="max-height:200px; overflow-y:auto;">
                            @foreach($posisiList as $pos)
                                <div class="form-check">
                                    <input class="form-check-input ubah-posisi" type="checkbox" name="idposisi[]"
                                        value="{{ $pos['idposisi'] }}" id="ubah-pos-{{ $pos['idposisi'] }}">
                                    <label class="form-check-label" for="ubah-pos-{{ $pos['idposisi'] }}">
                                        {{ $pos['namaposisi'] }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Status</label>
                        <select name="status" id="ubahStatus" class="form-select" required>
                            <option value="Offshore">Offshore</option>
                            <option value="Onshore">Onshore</option>
                        </select>
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
    $(document).ready(function () {
        $('#tabelPersonel').DataTable({
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                emptyTable: 'Belum ada data personel.',
                zeroRecords: 'Data tidak ditemukan.',
            },
            columnDefs: [{ orderable: false, targets: 3 }]
        });
    });

    document.querySelectorAll('.btn-ubah').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.getElementById('formUbahPersonel').action =
                '/gudang/{{ $idgudang }}/personel/' + this.dataset.id;
            document.getElementById('ubahStatus').value = this.dataset.status;

            var posisiIds = this.dataset.posisi ? this.dataset.posisi.split(',') : [];
            document.querySelectorAll('.ubah-posisi').forEach(function (cb) {
                cb.checked = posisiIds.includes(cb.value);
            });
        });
    });
</script>
@endpush

@endsection
