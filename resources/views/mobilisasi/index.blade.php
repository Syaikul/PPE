@extends('layouts.kai')

@section('page_title', 'Mobilisasi — ' . ($gudang['namagudang'] ?? 'Gudang'))

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
    <a href="{{ route('gudang.mobilisasi.create', $idgudang) }}" class="btn btn-primary rounded-pill px-4">
        <i class="fas fa-plus me-1"></i> Tambah Mobilisasi
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title">Daftar Mobilisasi</h4>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="tabelMobilisasi" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>SR</th>
                        <th>Lokasi Pekerjaan</th>
                        <th class="text-center">Jumlah Personil</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($mobilisasiList as $mob)
                        <tr>
                            <td class="fw-bold">{{ $mob->sr ?: '-' }}</td>
                            <td>{{ $mob->lokasi_pekerjaan ?: '-' }}</td>
                            <td class="text-center">{{ $mob->personel_count }}</td>
                            <td>
                                @php
                                    $badge = $mob->status === 'berjalan' ? 'bg-info' : ($mob->status === 'selesai' ? 'bg-success' : 'bg-warning text-dark');
                                    $label = $mob->status === 'berjalan' ? 'Sedang Berjalan' : ucfirst($mob->status);
                                @endphp
                                <span class="badge {{ $badge }}">{{ $label }}</span>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('gudang.mobilisasi.show', [$idgudang, $mob->id]) }}"
                                    class="btn btn-sm btn-success">Detail</a>
                                <form action="{{ route('gudang.mobilisasi.destroy', [$idgudang, $mob->id]) }}"
                                    method="POST" class="d-inline" onsubmit="return confirm('Hapus mobilisasi ini? Personel akan kembali Offshore.')">
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

@push('scripts')
<script>
    $(document).ready(function () {
        $('#tabelMobilisasi').DataTable({
            language: {
                lengthMenu: 'Tampilkan _MENU_ data',
                search: 'Cari:',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
                infoEmpty: 'Menampilkan 0 sampai 0 dari 0 data',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                emptyTable: 'Belum ada data mobilisasi.',
                zeroRecords: 'Data tidak ditemukan.',
            },
            columnDefs: [{ orderable: false, targets: 4 }]
        });
    });
</script>
@endpush

@endsection
