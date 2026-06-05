@extends('layouts.kai')

@section('page_title', 'PPE Masuk — ' . ($gudang['namagudang'] ?? 'Gudang'))

@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-warehouse me-1"></i> Ganti Gudang
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">{{ $gudang['namagudang'] ?? 'Gudang #'.$idgudang }}</span>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title">PPE Masuk</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelPpeMasuk" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>MR</th>
                        <th>Nama Barang</th>
                        <th class="text-center">QTY</th>
                        <th>Tanggal Sampai</th>
                        <th>No PO</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($kedatanganList as $k)
                        @php
                            $item = $k->item;
                            $mr = $item->permintaan;
                            $nama = $varianMap[$item->idbarangvarian]['label'] ?? 'Barang #'.$item->idbarangvarian;
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $mr->nomor_mr }}</td>
                            <td>{{ $nama }}</td>
                            <td class="text-center">
                                <span class="badge bg-success">{{ $k->qty_datang }}</span>
                            </td>
                            <td>{{ $k->tanggal->format('j M Y') }}</td>
                            <td>{{ $k->no_po ?? '-' }}</td>
                            <td>{{ $k->catatan ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        $('#tabelPpeMasuk').DataTable({
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                infoFiltered: '(difilter dari _MAX_ total data)',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                emptyTable: 'Belum ada data PPE masuk.',
                zeroRecords: 'Data tidak ditemukan.',
            },
            order: [[3, 'desc']]
        });
    });
</script>
@endpush

@endsection
