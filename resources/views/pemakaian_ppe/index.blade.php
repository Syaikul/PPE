@extends('layouts.kai')

@section('page_title', 'Data Pemakaian PPE — ' . ($gudang['namagudang'] ?? 'Gudang'))

@section('content')

<style>
    .pemakaian-scroll { overflow-x: auto; }
    .pemakaian-table { min-width: 800px; }
    .pemakaian-table th.col-nama,
    .pemakaian-table td.col-nama {
        position: sticky;
        left: 0;
        z-index: 2;
        background-color: #fff;
        min-width: 220px;
        box-shadow: 2px 0 4px -2px rgba(0,0,0,.15);
    }
    .pemakaian-table thead th.col-nama {
        z-index: 3;
        background-color: #f8f9fa;
    }
</style>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-warehouse me-1"></i> Ganti Gudang
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">{{ $gudang['namagudang'] ?? 'Gudang #'.$idgudang }}</span>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h4 class="card-title mb-0">Data Pemakaian PPE</h4>
        <small class="text-muted">Jumlah = berapa kali personel meminta item (Non Consumable, lintas gudang).</small>
    </div>
    <div class="card-body p-0">
        <div class="pemakaian-scroll">
            <table class="table table-hover align-middle mb-0 pemakaian-table">
                <thead class="table-light">
                    <tr>
                        <th class="col-nama">Nama Personel</th>
                        @foreach($columns as $col)
                            <th class="text-center text-nowrap">{{ strtoupper($col['label']) }}</th>
                        @endforeach
                        @if($columns->isEmpty())
                            <th class="text-center text-muted">Belum ada pemakaian</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        <tr>
                            <td class="col-nama fw-semibold">
                                <a href="{{ route('gudang.pemakaian-ppe.show', [$idgudang, $row['personel_id']]) }}">
                                    {{ $row['nama'] }}
                                </a>
                            </td>
                            @foreach($columns as $col)
                                @php $c = $row['counts'][$col['idsubbarang']] ?? 0; @endphp
                                <td class="text-center">
                                    @if($c > 0)
                                        <span class="badge {{ $c > 1 ? 'bg-warning text-dark' : 'bg-light text-dark border' }}">{{ $c }}</span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                            @endforeach
                            @if($columns->isEmpty())<td></td>@endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ $columns->count() + 1 }}" class="text-center text-muted py-4">Belum ada personel.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
