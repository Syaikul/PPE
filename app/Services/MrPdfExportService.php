<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class MrPdfExportService
{
    /**
     * @param  array<string, mixed>  $gudang
     * @param  array<int, array{label: string, jumlah: int, satuan: string, sisa_stok: int, kategori: string}>  $items
     */
    public function download(array $gudang, string $nomorMr, string $tanggal, array $items): Response
    {
        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $namagudang = $gudang['namagudang'] ?? 'Gudang';
        $nomorKontrak = $gudang['nomorkontrak'] ?? '-';
        $projectLabel = 'Project '.($bulanIndo[(int) date('n')] ?? '').' '.date('Y');

        $minRows = max(5, count($items));
        $emptyRows = max(0, $minRows - count($items));

        $pdf = Pdf::loadView('permintaan.pdf_mr', [
            'nomorMr'        => $nomorMr,
            'tanggal'        => $tanggal,
            'namagudang'     => $namagudang,
            'nomorKontrak'   => $nomorKontrak,
            'projectLabel'   => $projectLabel,
            'items'          => $items,
            'emptyRows'      => $emptyRows,
            'logoPath'       => public_path('template/assets/img/logoicon.png'),
            'signaturePath'  => public_path('template/assets/img/Untitled.png'),
        ])->setPaper('a4', 'landscape');

        $filename = 'MR-'.preg_replace('/[^\w\-]+/u', '_', $nomorMr).'.pdf';

        return $pdf->download($filename);
    }
}
