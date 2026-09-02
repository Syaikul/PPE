<?php

namespace App\Services;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MrExcelExportService
{
    private const SHEET_NAME = 'MR ONWJ';

    private const ITEM_START_ROW = 25;

    private const TEMPLATE_ITEM_ROWS = 5;

    /** @var list<string> */
    private const ITEM_MERGES = [
        'B:C',
        'D:R',
        'S:AG',
        'AH:AJ',
        'AK:AW',
        'AX:AZ',
    ];

    public const BULAN_ID = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function __construct(
        ?string $templatePath = null,
        ?string $logoPath = null,
        ?string $signaturePath = null,
    ) {
        $this->templatePath = $templatePath ?? $this->projectPath('resources/templates/mr-ppe-iwes.xlsx');
        $this->logoPath = $logoPath ?? $this->publicPath('images/favicon.png');
        $this->signaturePath = $signaturePath ?? $this->publicPath('template/assets/img/ttd.png');
    }

    private string $templatePath;

    private string $logoPath;

    private string $signaturePath;

    /**
     * @param  array<string, mixed>  $gudang
     * @param  array<int, array{label: string, jumlah: int, sisa_stok: int}>  $items
     */
    public function download(array $gudang, CarbonInterface $tanggal, array $items): BinaryFileResponse
    {
        $spreadsheet = $this->makeSpreadsheet($gudang, $tanggal, $items);
        $filename = $this->filename($gudang, $tanggal);

        $temp = tempnam(sys_get_temp_dir(), 'mr');
        if ($temp === false) {
            throw new RuntimeException('Gagal membuat file sementara untuk Excel.');
        }

        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($temp);
        $spreadsheet->disconnectWorksheets();

        return response()->download($temp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * @param  array<string, mixed>  $gudang
     * @param  array<int, array{label: string, jumlah: int, sisa_stok: int}>  $items
     */
    public function makeSpreadsheet(array $gudang, CarbonInterface $tanggal, array $items): Spreadsheet
    {
        if (! is_file($this->templatePath)) {
            throw new RuntimeException('Template Excel permintaan tidak ditemukan.');
        }

        $spreadsheet = IOFactory::load($this->templatePath);
        $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME) ?? $spreadsheet->getActiveSheet();

        $namaGudang = trim((string) ($gudang['namagudang'] ?? 'Gudang'));
        $bulan = self::BULAN_ID[(int) $tanggal->month] ?? $tanggal->format('F');
        $tahun = (int) $tanggal->year;

        $mrNo = (string) $sheet->getCell('B9')->getValue();
        $sheet->setCellValue('B9', preg_replace('#/ONWJ/#', '/'.$namaGudang.'/', $mrNo, 1));

        $sheet->setCellValue(
            'R9',
            'Tanggal : '.$tanggal->day.' '.$bulan.' '.$tahun
        );

        $sheet->setCellValue(
            'B11',
            "Nama & Nomor Kontrak :\nTechnical Service Contract for Fabrication, Installation & Maintenance"
        );

        $sheet->setCellValue(
            'B14',
            "JN & Job Title / For :\nPPE & IWES Personil Offshore {$namaGudang} Project {$bulan} {$tahun}"
        );

        $this->replaceImages($sheet);
        $this->writeItems($sheet, $items);
        $this->clearKeteranganHighlight($sheet);

        return $spreadsheet;
    }

    /**
     * @param  array<string, mixed>  $gudang
     */
    public function filename(array $gudang, CarbonInterface $tanggal): string
    {
        $namaGudang = trim((string) ($gudang['namagudang'] ?? 'Gudang'));
        $bulan = self::BULAN_ID[(int) $tanggal->month] ?? $tanggal->format('F');
        $raw = 'MR PPE & IWES HSE - '.$namaGudang.' '.$tanggal->day.' '.$bulan.' '.$tanggal->year.'.xlsx';

        return str_replace(['\\', '/', ':', '*', '?', '"', '<', '>', '|'], '-', $raw);
    }

    /**
     * @param  array<int, array{label: string, jumlah: int, sisa_stok: int}>  $items
     */
    private function writeItems(Worksheet $sheet, array $items): void
    {
        $count = count($items);
        $extra = $count - self::TEMPLATE_ITEM_ROWS;

        if ($extra > 0) {
            $insertAt = self::ITEM_START_ROW + self::TEMPLATE_ITEM_ROWS;
            $sheet->insertNewRowBefore($insertAt, $extra);

            for ($i = 0; $i < $extra; $i++) {
                $this->cloneItemRowLayout($sheet, self::ITEM_START_ROW, $insertAt + $i);
            }
        }

        foreach ($items as $i => $item) {
            $this->fillItemRow($sheet, self::ITEM_START_ROW + $i, $i + 1, $item);
        }

        if ($count < self::TEMPLATE_ITEM_ROWS) {
            $sheet->removeRow(
                self::ITEM_START_ROW + $count,
                self::TEMPLATE_ITEM_ROWS - $count
            );
        }
    }

    /**
     * @param  array{label: string, jumlah: int, sisa_stok: int}  $item
     */
    private function fillItemRow(Worksheet $sheet, int $row, int $number, array $item): void
    {
        $this->ensureItemRowMerges($sheet, $row);

        $sheet->setCellValue('B'.$row, $number);
        $sheet->setCellValue('D'.$row, $item['label']);
        $sheet->setCellValue('S'.$row, '-');
        $sheet->setCellValue('AH'.$row, (int) $item['jumlah']);
        $sheet->setCellValue('AK'.$row, 'Pcs');
        $sheet->setCellValue('AX'.$row, 'Sisa '.(int) $item['sisa_stok'].' Pcs');
        $sheet->setCellValue('BA'.$row, '√');
        $sheet->setCellValue('BH'.$row, '√');
    }

    private function cloneItemRowLayout(Worksheet $sheet, int $fromRow, int $toRow): void
    {
        $highestCol = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $fromRange = 'A'.$fromRow.':'.Coordinate::stringFromColumnIndex($highestCol).$fromRow;
        $toRange = 'A'.$toRow.':'.Coordinate::stringFromColumnIndex($highestCol).$toRow;
        $sheet->duplicateStyle($sheet->getStyle($fromRange), $toRange);

        $fromHeight = $sheet->getRowDimension($fromRow)->getRowHeight();
        if ($fromHeight > 0) {
            $sheet->getRowDimension($toRow)->setRowHeight($fromHeight);
        }

        $this->ensureItemRowMerges($sheet, $toRow);
    }

    private function ensureItemRowMerges(Worksheet $sheet, int $row): void
    {
        foreach (self::ITEM_MERGES as $pair) {
            [$start, $end] = explode(':', $pair);
            $this->mergeIfNeeded($sheet, $start.$row.':'.$end.$row);
        }
    }

    private function mergeIfNeeded(Worksheet $sheet, string $range): void
    {
        $range = strtoupper($range);

        foreach ($sheet->getMergeCells() as $merged) {
            if (strtoupper((string) $merged) === $range) {
                return;
            }
        }

        $sheet->mergeCells($range);
    }

    private function clearKeteranganHighlight(Worksheet $sheet): void
    {
        foreach (array_keys($sheet->getConditionalStylesCollection()) as $range) {
            if (stripos((string) $range, 'AX') !== false) {
                $sheet->removeConditionalStyles((string) $range);
            }
        }

        $sheet->getStyle('AX27:AZ29')->getFill()->setFillType(Fill::FILL_NONE);
    }

    private function replaceImages(Worksheet $sheet): void
    {
        $pictures = [];
        foreach ($sheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof Drawing) {
                $pictures[] = $drawing;
            }
        }

        usort(
            $pictures,
            fn (Drawing $a, Drawing $b) => $this->drawingRow($a) <=> $this->drawingRow($b)
        );

        if ($pictures === []) {
            return;
        }

        $this->swapImage($pictures[0], $this->logoPath);

        $last = $pictures[array_key_last($pictures)];
        if ($last !== $pictures[0]) {
            $this->swapImage($last, $this->signaturePath);
        }
    }

    private function drawingRow(Drawing $drawing): int
    {
        return (int) preg_replace('/\D+/', '', $drawing->getCoordinates()) ?: 0;
    }

    private function swapImage(Drawing $drawing, string $path): void
    {
        if (! is_file($path)) {
            return;
        }

        $height = $drawing->getHeight();
        $width = $drawing->getWidth();
        $drawing->setResizeProportional(true);
        $drawing->setPath($path, true);

        if ($height > 0) {
            $drawing->setHeight($height);
        } elseif ($width > 0) {
            $drawing->setWidth($width);
        }
    }

    private function publicPath(string $relative): string
    {
        return $this->projectPath('public/'.$relative);
    }

    private function projectPath(string $relative): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $relative);
    }
}
