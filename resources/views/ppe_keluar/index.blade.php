@extends('layouts.kai')

@section('page_title', 'PPE Keluar — ' . ($gudang['namagudang'] ?? 'Gudang'))

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

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-warehouse me-1"></i> Ganti Gudang
    </a>
    <span class="text-muted">/</span>
    <span class="fw-semibold">{{ $gudang['namagudang'] ?? 'Gudang #'.$idgudang }}</span>
</div>

<div class="card shadow-sm">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="card-title mb-0">PPE Keluar</h4>
            <small class="text-muted">Termasuk pengeluaran dari mobilisasi, spare, transfer, dan form barang keluar di halaman ini.</small>
        </div>
        @canCrud('ppe_keluar')
        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#formKeluarWrap"
            {{ empty($ppeOptions) || $personelOptions->isEmpty() ? 'disabled' : '' }}>
            Barang Keluar +
        </button>
        @endcanCrud
    </div>

    @canCrud('ppe_keluar')
    <div class="collapse border-bottom {{ $errors->any() || old('stok_id') ? 'show' : '' }}" id="formKeluarWrap">
        <div class="card-body">
            @if(empty($ppeOptions))
                <p class="text-muted mb-0">Belum ada stok yang bisa dikeluarkan. Tambahkan stok di menu Data Stok terlebih dahulu.</p>
            @elseif($personelOptions->isEmpty())
                <p class="text-muted mb-0">Belum ada personel di gudang ini. Tambahkan di Data Personel untuk memilih penerima.</p>
            @else
                <p class="text-muted small mb-3">
                    Form ini untuk barang keluar di luar Mob-Demob. Stok gudang langsung dikurangi dan tercatat di tabel di bawah.
                </p>
                <form action="{{ route('gudang.ppe-keluar.store', $idgudang) }}" method="POST" id="formBarangKeluar">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Nama PPE (Sub Barang) <span class="text-danger">*</span></label>
                            <select id="keluarNamaPpe" class="form-select" required>
                                <option value="" disabled {{ old('stok_id') ? '' : 'selected' }}>— Pilih PPE —</option>
                                @foreach($ppeOptions as $ppe)
                                    <option value="{{ $ppe['idsubbarang'] }}">{{ $ppe['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Varian <span class="text-danger">*</span></label>
                            <select id="keluarVarian" class="form-select" required disabled>
                                <option value="">— Pilih PPE dulu —</option>
                            </select>
                            <input type="hidden" name="stok_id" id="keluarStokId" value="{{ old('stok_id') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">QTY <span class="text-danger">*</span></label>
                            <input type="number" name="qty" id="keluarQty" class="form-control" min="1"
                                value="{{ old('qty') }}" required disabled>
                            <small id="keluarQtyHint" class="text-muted">Stok tersedia: -</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control"
                                value="{{ old('tanggal', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Penerima <span class="text-danger">*</span></label>
                            <select name="personel_id" class="form-select" required>
                                <option value="" disabled {{ old('personel_id') ? '' : 'selected' }}>— Pilih Personel —</option>
                                @foreach($personelOptions as $opt)
                                    <option value="{{ $opt['id'] }}" {{ (string) old('personel_id') === (string) $opt['id'] ? 'selected' : '' }}>
                                        {{ $opt['nama'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold">Catatan</label>
                            <input type="text" name="catatan" class="form-control" value="{{ old('catatan') }}"
                                placeholder="Opsional">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-sign-out-alt me-1"></i> Simpan Barang Keluar
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
    @endcanCrud

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
                            $hasPersonel = $row->personel || $row->idpersonel;
                            if ($row->personel) {
                                $penerima = $personelMapApi[$row->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$row->personel->idpersonel;
                            } elseif ($row->idpersonel) {
                                $penerima = $personelMapApi[$row->idpersonel]['namapersonel'] ?? 'Personel #'.$row->idpersonel;
                            } else {
                                $penerima = $row->catatan ?: '-';
                            }
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $namaPpe }}</td>
                            <td>{{ $namaVarian }}</td>
                            <td class="text-center">{{ $row->qty }}</td>
                            <td>{{ $row->tanggal->format('d/m/Y') }}</td>
                            <td>{{ $penerima }}</td>
                            <td>{{ $hasPersonel ? ($row->catatan ?: '-') : '-' }}</td>
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
            order: [[3, 'desc']],
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

        var ppeOptions = @json($ppeOptions);
        var oldStokId = @json(old('stok_id'));

        var namaSelect = document.getElementById('keluarNamaPpe');
        var varianSelect = document.getElementById('keluarVarian');
        var stokIdInput = document.getElementById('keluarStokId');
        var qtyInput = document.getElementById('keluarQty');
        var qtyHint = document.getElementById('keluarQtyHint');

        if (!namaSelect || !varianSelect) return;

        function findPpe(idsub) {
            return ppeOptions.find(function (p) { return String(p.idsubbarang) === String(idsub); }) || null;
        }

        function applyVarian(stokId) {
            var opt = varianSelect.options[varianSelect.selectedIndex];
            var qty = opt && opt.dataset.qty ? parseInt(opt.dataset.qty, 10) : 0;
            stokIdInput.value = stokId || '';
            qtyInput.disabled = !stokId;
            qtyInput.max = qty > 0 ? qty : '';
            qtyHint.textContent = stokId ? ('Stok tersedia: ' + qty) : 'Stok tersedia: -';
            if (qtyInput.value && qty > 0 && parseInt(qtyInput.value, 10) > qty) {
                qtyInput.value = qty;
            }
        }

        function hasRealVarian(ppe) {
            return ppe.varian.some(function (v) { return v.idbarangvarian; });
        }

        function fillVarian(idsub, selectedStokId) {
            var ppe = findPpe(idsub);
            varianSelect.innerHTML = '';
            if (!ppe) {
                varianSelect.disabled = true;
                varianSelect.required = false;
                varianSelect.innerHTML = '<option value="">— Pilih PPE dulu —</option>';
                applyVarian('');
                return;
            }

            var perluVarian = hasRealVarian(ppe);

            ppe.varian.forEach(function (v) {
                var el = document.createElement('option');
                el.value = v.stok_id;
                el.dataset.qty = v.qty;
                el.textContent = v.label;
                if (selectedStokId && String(selectedStokId) === String(v.stok_id)) {
                    el.selected = true;
                }
                varianSelect.appendChild(el);
            });

            if (perluVarian) {
                varianSelect.disabled = false;
                varianSelect.required = true;
                if (ppe.varian.length > 1 && !selectedStokId) {
                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.disabled = true;
                    placeholder.selected = true;
                    placeholder.textContent = '— Pilih Varian —';
                    varianSelect.insertBefore(placeholder, varianSelect.firstChild);
                }
            } else {
                varianSelect.disabled = true;
                varianSelect.required = false;
                if (!selectedStokId && ppe.varian.length === 1) {
                    varianSelect.value = ppe.varian[0].stok_id;
                }
            }

            applyVarian(varianSelect.value);
        }

        namaSelect.addEventListener('change', function () {
            fillVarian(this.value, '');
        });

        varianSelect.addEventListener('change', function () {
            applyVarian(this.value);
        });

        if (oldStokId) {
            var matched = ppeOptions.find(function (p) {
                return p.varian.some(function (v) { return String(v.stok_id) === String(oldStokId); });
            });
            if (matched) {
                namaSelect.value = matched.idsubbarang;
                fillVarian(matched.idsubbarang, oldStokId);
            }
        }

        document.getElementById('formBarangKeluar')?.addEventListener('submit', function (e) {
            if (!stokIdInput.value) {
                e.preventDefault();
                alert('Pilih Nama PPE dan Varian terlebih dahulu.');
            }
        });
    });
</script>
@endpush

@endsection
