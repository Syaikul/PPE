@php
    $showActions = $showActions ?? false;
    $tglLabel = $mobilisasi->created_at
        ? $mobilisasi->created_at->format('d M y')
        : '-';
    $colspan = $showActions ? 8 : 7;
@endphp

<div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="ps-3" style="width:80px">SR</th>
                <th style="width:110px">Tanggal</th>
                <th>Item</th>
                <th class="text-center" style="width:90px">Jumlah</th>
                <th class="text-center" style="width:110px">Sisa</th>
                <th class="text-center" style="width:90px">Dipakai</th>
                <th style="width:180px">Penanggung Jawab</th>
                @if($showActions)
                    <th style="width:160px">Aksi</th>
                @endif
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
                @endphp
                @foreach($spareItems as $idx => $item)
                    @php
                        $labelItem = \App\Services\SpareBarangService::labelForItem(
                            $item->idsubbarang, $item->idbarangvarian, $subBarangMap, $varianMap
                        );
                    @endphp
                    <tr>
                        @if($idx === 0)
                            <td rowspan="{{ $rowspan }}" class="ps-3 fw-bold">{{ $mobilisasi->sr ?: ($sr->no_sr ?: '-') }}</td>
                            <td rowspan="{{ $rowspan }}">{{ $tglLabel }}</td>
                        @endif
                        <td>
                            {{ $labelItem }}
                            @if($item->isReturned())
                                <span class="badge bg-secondary ms-1">Selesai</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->jumlah }}</td>
                        <td class="text-center">
                            @if($showActions && ! $item->isReturned())
                                <input type="number" class="form-control form-control-sm text-center spare-sisa-input"
                                    form="formKembaliSpare{{ $sr->id }}"
                                    name="sisa[{{ $item->id }}]"
                                    data-item-id="{{ $item->id }}"
                                    data-jumlah="{{ $item->jumlah }}"
                                    value="{{ $item->sisa }}" min="0" max="{{ $item->sisa }}" required>
                            @else
                                <span class="badge {{ $item->sisa > 0 ? 'bg-success' : 'bg-secondary' }}">{{ $item->sisa }}</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="spare-dipakai" data-item="{{ $item->id }}">{{ $item->qtyDipakai() }}</span>
                        </td>
                        @if($idx === 0)
                            <td rowspan="{{ $rowspan }}">{{ $namaPj }}</td>
                            @if($showActions)
                                <td rowspan="{{ $rowspan }}">
                                    @canCrud('demobilisasi')
                                    @if($srReturned)
                                        <span class="text-muted">Sudah diselesaikan</span>
                                    @else
                                        <form id="formKembaliSpare{{ $sr->id }}"
                                            action="{{ route('gudang.mobilisasi.spare.kembalikan', [$idgudang, $mobilisasi->id, $sr->id]) }}"
                                            method="POST"
                                            onsubmit="return confirm('Sisa dikembalikan ke stok. Selisih (jumlah − sisa) langsung masuk PPE Keluar atas nama penanggung jawab. Lanjutkan?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">Selesaikan Spare</button>
                                        </form>
                                    @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endcanCrud
                                </td>
                            @endif
                        @endif
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="{{ $colspan }}" class="text-center text-muted py-3">Belum ada spare barang untuk mobilisasi ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

@if($showActions)
@push('scripts')
<script>
    document.querySelectorAll('.spare-sisa-input').forEach(function (input) {
        function refresh() {
            var jumlah = parseInt(input.dataset.jumlah, 10) || 0;
            var sisa = parseInt(input.value, 10);
            if (isNaN(sisa)) sisa = 0;
            var dipakai = Math.max(0, jumlah - sisa);
            var target = document.querySelector('.spare-dipakai[data-item="' + input.dataset.itemId + '"]');
            if (target) target.textContent = dipakai;
        }
        input.addEventListener('input', refresh);
        refresh();
    });
</script>
@endpush
@endif
