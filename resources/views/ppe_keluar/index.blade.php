@extends('layouts.kai')

@section('page_title', 'PPE Keluar — ' . ($gudang['namagudang'] ?? 'Gudang'))

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
        <h4 class="card-title">PPE Keluar</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelPpeKeluar" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>Nama PPE (Sub Barang)</th>
                        <th>Varian</th>
                        <th class="text-center">QTY</th>
                        <th>Tanggal</th>
                        <th>Penerima</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($keluarList as $row)
                        @php
                            $namaPpe = $subBarangMap[$row->idsubbarang]['label'] ?? 'Item #'.$row->idsubbarang;
                            $namaVarian = $row->idbarangvarian
                                ? ($varianMap[$row->idbarangvarian]['label'] ?? 'Varian #'.$row->idbarangvarian)
                                : '-';
                            $penerima = $row->personel
                                ? ($personelMapApi[$row->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$row->personel->idpersonel)
                                : ($personelMapApi[$row->idpersonel]['namapersonel'] ?? 'Personel #'.$row->idpersonel);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $namaPpe }}</td>
                            <td>{{ $namaVarian }}</td>
                            <td class="text-center">{{ $row->qty }}</td>
                            <td>{{ $row->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $penerima }}</td>
                            <td>{{ $row->catatan ?: '-' }}</td>
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
        $('#tabelPpeKeluar').DataTable({
            order: [[2, 'desc']],
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                emptyTable: 'Belum ada data PPE keluar.',
                zeroRecords: 'Data tidak ditemukan.',
            },
        });
    });
</script>
@endpush

@endsection
