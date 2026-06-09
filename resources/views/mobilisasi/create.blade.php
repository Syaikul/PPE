@extends('layouts.kai')

@section('page_title', 'Tambah Mobilisasi')

@section('content')

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

<div class="mb-3">
    <a href="{{ route('gudang.mobilisasi', $idgudang) }}" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
    </a>
</div>

<form action="{{ route('gudang.mobilisasi.store', $idgudang) }}" method="POST" id="formMobilisasi">
    @csrf

    {{-- Header: SR + Lokasi --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header border-start border-4 border-primary">
            <h4 class="card-title mb-0">Mobilisasi</h4>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label fw-semibold">SR</label>
                <input type="text" name="sr" class="form-control" placeholder="Masukkan SR / nomor mobilisasi...">
            </div>
            <div class="mb-0">
                <label class="form-label fw-semibold">Lokasi Pekerjaan</label>
                <input type="text" name="lokasi_pekerjaan" class="form-control" placeholder="Masukkan lokasi pekerjaan...">
            </div>
        </div>
    </div>

    {{-- Personel --}}
    <div class="card shadow-sm">
        <div class="card-body bg-light border-start border-4 border-info mb-2">
            <span class="me-3">Total Personil: <strong class="text-primary">{{ $personelList->count() }}</strong></span>
            <span>Terpilih: <strong class="text-primary" id="countTerpilih">0</strong></span>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th style="width:120px">
                                <button type="button" class="btn btn-sm btn-success rounded-pill" id="btnPilihSemua">Pilih Semua</button>
                            </th>
                            <th>NAMA PERSONEL</th>
                            <th>POSISI</th>
                            <th style="width:35%">POSISI YANG DIGUNAKAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($personelList as $p)
                            <tr>
                                <td>
                                    <input type="checkbox" name="personel[]" value="{{ $p['id'] }}"
                                        class="form-check-input chk-personel" style="width:1.4rem;height:1.4rem;">
                                </td>
                                <td class="fw-semibold">{{ $p['nama'] }}</td>
                                <td class="text-muted">{{ $p['posisi_lbl'] ?: '-' }}</td>
                                <td>
                                    <select name="posisi[{{ $p['id'] }}][]" class="form-select posisi-select" multiple>
                                        @foreach($posisiMap as $idposisi => $pos)
                                            <option value="{{ $idposisi }}"
                                                {{ in_array($idposisi, $p['posisi_ids']) ? 'selected' : '' }}>
                                                {{ $pos['namaposisi'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    Tidak ada personel yang tersedia. Personel yang masih Onshore (mobilisasi aktif) tidak ditampilkan. Tambahkan personel baru di Data Personel atau selesaikan demobilisasi terlebih dahulu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary px-4" id="btnSimpan">Simpan Mobilisasi</button>
        </div>
    </div>
</form>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        $('.posisi-select').select2({
            placeholder: 'Pilih posisi...',
            width: '100%',
        });

        function updateCount() {
            var n = document.querySelectorAll('.chk-personel:checked').length;
            document.getElementById('countTerpilih').textContent = n;
        }

        document.querySelectorAll('.chk-personel').forEach(function (cb) {
            cb.addEventListener('change', updateCount);
        });

        document.getElementById('btnPilihSemua').addEventListener('click', function () {
            var boxes = document.querySelectorAll('.chk-personel');
            var allChecked = [...boxes].every(c => c.checked);
            boxes.forEach(c => c.checked = !allChecked);
            this.textContent = allChecked ? 'Pilih Semua' : 'Batal Pilih';
            updateCount();
        });

        document.getElementById('formMobilisasi').addEventListener('submit', function (e) {
            if (document.querySelectorAll('.chk-personel:checked').length === 0) {
                e.preventDefault();
                alert('Pilih minimal 1 personel untuk dimobilisasi.');
            }
        });
    });
</script>
@endpush

@endsection
