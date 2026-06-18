<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Material Request — {{ $nomorMr }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #111; margin: 0; padding: 14px 16px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { border: 1px solid #333; padding: 4px 5px; vertical-align: middle; }
        .no-border td, .no-border th { border: none; }
        .header-title { font-size: 13px; font-weight: bold; text-align: center; line-height: 1.35; }
        .doc-box td { border: 1px solid #333; font-size: 8px; padding: 3px 6px; }
        .label { font-weight: bold; white-space: nowrap; }
        .center { text-align: center; }
        .left { text-align: left; }
        .bg-head { background: #e8e8e8; font-weight: bold; }
        .sign-box { height: 70px; vertical-align: bottom; text-align: center; }
        .sign-role { font-size: 8px; font-weight: bold; padding-top: 4px; }
        .sign-img { max-height: 42px; max-width: 90px; }
        .logo { max-height: 58px; max-width: 120px; }
        .note { font-size: 8px; text-align: right; margin-top: 6px; }
    </style>
</head>
<body>

{{-- Header --}}
<table class="no-border" style="margin-bottom: 6px;">
    <tr>
        <td style="width: 18%; vertical-align: top;">
            @if(is_file($logoPath))
                <img src="{{ $logoPath }}" class="logo" alt="Logo">
            @endif
        </td>
        <td style="width: 52%; vertical-align: middle;" class="header-title">
            SURAT PERMINTAAN BARANG / JASA<br>
            (MATERIAL/SERVICE REQUEST)
        </td>
        <td style="width: 30%; vertical-align: top;">
            <table class="doc-box" style="width: 100%;">
                <tr><td>No. Dokumen : F-PM-01-PRC-01-02</td></tr>
                <tr><td>Tanggal : 21/10/2019</td></tr>
                <tr><td>Revisi : Rev. 06</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- Info fields --}}
<table style="margin-bottom: 8px;">
    <tr>
        <td class="label" style="width: 12%;">MR No.</td>
        <td style="width: 20%;">{{ $nomorMr }}</td>
        <td class="label" style="width: 10%;">Tanggal</td>
        <td style="width: 14%;">{{ $tanggal }}</td>
        <td class="label" style="width: 12%;">Kode Proyek</td>
        <td style="width: 32%;">TBA</td>
    </tr>
    <tr>
        <td class="label">Nama &amp; Nomor Kontrak</td>
        <td colspan="5" class="left">
            Technical Service Contract for Fabrication, Installation &amp; Maintenance (PAKET A) ({{ $namagudang }}) {{ $nomorKontrak }}
        </td>
    </tr>
    <tr>
        <td class="label">Tanggal dibutuhkan</td>
        <td>ASAP</td>
        <td class="label">Kepada</td>
        <td colspan="3">DIVISI/BAGIAN PROCUREMENT</td>
    </tr>
    <tr>
        <td class="label">JN &amp; Job Title / For</td>
        <td colspan="5" class="left">
            PPE &amp; IWES Personil Offshore ({{ $namagudang }}) {{ $projectLabel }}
        </td>
    </tr>
    <tr>
        <td class="label">Prioritas</td>
        <td colspan="2">[ ] Urgent &nbsp;&nbsp; [X] Normal</td>
        <td class="label">Data Pendukung</td>
        <td colspan="2">[ ] Laba Rugi Prognosa &nbsp;&nbsp; [ ] WO/SR/COO</td>
    </tr>
</table>

{{-- Item table --}}
<table>
    <thead>
        <tr class="bg-head center">
            <th style="width: 4%;">ITEM</th>
            <th style="width: 24%;">JENIS PERMINTAAN</th>
            <th style="width: 14%;">SPESIFIKASI</th>
            <th style="width: 6%;">JUMLAH</th>
            <th style="width: 6%;">SATUAN</th>
            <th style="width: 14%;">KETERANGAN</th>
            <th style="width: 8%;">Consumable</th>
            <th style="width: 8%;">Non Asset</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
            @php $isConsumable = ($item['kategori'] ?? '') === 'Consumable'; @endphp
            <tr class="center">
                <td>{{ $i + 1 }}</td>
                <td class="left">{{ $item['label'] }}</td>
                <td></td>
                <td>{{ $item['jumlah'] }}</td>
                <td>{{ $item['satuan'] }}</td>
                <td class="left">Sisa stok {{ $item['sisa_stok'] }}</td>
                <td>{{ $isConsumable ? 'X' : '' }}</td>
                <td>{{ $isConsumable ? '' : 'X' }}</td>
            </tr>
        @endforeach
        @for($r = 0; $r < $emptyRows; $r++)
            <tr class="center">
                <td>{{ count($items) + $r + 1 }}</td>
                <td></td><td></td><td></td><td></td><td></td><td></td><td></td>
            </tr>
        @endfor
    </tbody>
</table>

{{-- Signatures --}}
<table style="margin-top: 14px;">
    <tr class="center bg-head">
        <th style="width: 20%;">Dibuat Oleh</th>
        <th style="width: 20%;">Diperiksa Oleh</th>
        <th style="width: 20%;">Disetujui Oleh</th>
        <th style="width: 20%;">Diketahui Oleh</th>
        <th style="width: 20%;">Diketahui Oleh</th>
    </tr>
    <tr>
        <td class="sign-box">
            @if(is_file($signaturePath))
                <img src="{{ $signaturePath }}" class="sign-img" alt="Tanda tangan">
            @endif
        </td>
        <td class="sign-box"></td>
        <td class="sign-box"></td>
        <td class="sign-box"></td>
        <td class="sign-box"></td>
    </tr>
    <tr class="center sign-role">
        <td>Peminta</td>
        <td>HSE Coordinator</td>
        <td>Atasan Peminta</td>
        <td>HSE</td>
        <td>KaDivre*</td>
    </tr>
</table>

<p class="note">Note : * Jika diperlukan</p>

</body>
</html>
