<?php

namespace Tests\Unit;

use App\Services\MrExcelExportService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class MrExcelExportServiceTest extends TestCase
{
    private string $templatePath;

    protected function setUp(): void
    {
        parent::setUp();

        if (! class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('Ekstensi PHP zip diperlukan untuk membaca template Excel.');
        }

        $this->templatePath = dirname(__DIR__, 2).'/resources/templates/mr-ppe-iwes.xlsx';

        if (! is_file($this->templatePath)) {
            $this->markTestSkipped('Template Excel permintaan tidak ditemukan.');
        }
    }

    public function test_job_title_follows_warehouse_and_request_month(): void
    {
        $sheet = $this->makeSheet(
            ['namagudang' => 'MEPN'],
            '2026-09-01',
            [
                ['label' => 'Bulsak', 'jumlah' => 10, 'sisa_stok' => 11],
            ]
        );

        $this->assertStringContainsString('MR No.', (string) $sheet->getCell('B9')->getValue());
        $this->assertStringContainsString('00XXXVI', (string) $sheet->getCell('B9')->getValue());
        $this->assertStringContainsString('/MEPN/', (string) $sheet->getCell('B9')->getValue());
        $this->assertStringNotContainsString('/ONWJ/', (string) $sheet->getCell('B9')->getValue());
        $this->assertSame('Tanggal : 1 September 2026', (string) $sheet->getCell('R9')->getValue());

        $b14 = (string) $sheet->getCell('B14')->getValue();
        $this->assertStringContainsString('JN & Job Title / For :', $b14);
        $this->assertStringContainsString('PPE & IWES Personil Offshore MEPN Project September 2026', $b14);
        $this->assertStringNotContainsString('ONWJ', $b14);
        $this->assertStringNotContainsString('Agustus', $b14);

        $b11 = (string) $sheet->getCell('B11')->getValue();
        $this->assertStringContainsString('Nama & Nomor Kontrak :', $b11);
        $this->assertStringContainsString('Technical Service Contract for Fabrication, Installation & Maintenance', $b11);
        $this->assertStringNotContainsString('PAKET A', $b11);
        $this->assertStringNotContainsString('Pertamina Offshore North West Java', $b11);
        $this->assertStringNotContainsString('CONTRACT NO.', $b11);
    }

    public function test_item_row_uses_name_dash_spec_and_sisa(): void
    {
        $sheet = $this->makeSheet(
            ['namagudang' => 'ONWJ'],
            '2026-08-04',
            [
                ['label' => 'Chinstrap Color Red', 'jumlah' => 50, 'sisa_stok' => 3],
            ]
        );

        $this->assertSame(1, (int) $sheet->getCell('B25')->getValue());
        $this->assertSame('Chinstrap Color Red', (string) $sheet->getCell('D25')->getValue());
        $this->assertSame('-', (string) $sheet->getCell('S25')->getValue());
        $this->assertSame(50, (int) $sheet->getCell('AH25')->getValue());
        $this->assertSame('Pcs', (string) $sheet->getCell('AK25')->getValue());
        $this->assertSame('Sisa 3 Pcs', (string) $sheet->getCell('AX25')->getValue());
        $this->assertSame('√', (string) $sheet->getCell('BA25')->getValue());
        $this->assertSame('√', (string) $sheet->getCell('BH25')->getValue());
        $this->assertStringContainsString('Catatan', (string) $sheet->getCell('AN26')->getValue());
        $this->assertNotContains('D26:R26', array_map('strtoupper', array_keys($sheet->getMergeCells())));
    }

    public function test_extra_items_insert_rows_and_keep_footer(): void
    {
        $items = [];
        for ($i = 1; $i <= 6; $i++) {
            $items[] = [
                'label' => 'Item '.$i,
                'jumlah' => $i,
                'sisa_stok' => $i + 10,
            ];
        }

        $sheet = $this->makeSheet(['namagudang' => 'ONWJ'], '2026-08-04', $items);

        $this->assertSame('Item 6', (string) $sheet->getCell('D30')->getValue());
        $this->assertSame('-', (string) $sheet->getCell('S30')->getValue());
        $this->assertStringContainsString('Catatan', (string) $sheet->getCell('AN31')->getValue());
    }

    public function test_filename_uses_gudang_and_tanggal(): void
    {
        $service = new MrExcelExportService($this->templatePath);
        $name = $service->filename(['namagudang' => 'ONWJ'], Carbon::parse('2026-08-04'));

        $this->assertSame('MR PPE & IWES HSE - ONWJ 4 Agustus 2026.xlsx', $name);
    }

    public function test_logo_and_signature_use_app_images(): void
    {
        $sheet = $this->makeSheet(
            ['namagudang' => 'ONWJ'],
            '2026-08-04',
            [
                ['label' => 'Bulsak', 'jumlah' => 10, 'sisa_stok' => 11],
            ]
        );

        $paths = [];
        foreach ($sheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof \PhpOffice\PhpSpreadsheet\Worksheet\Drawing) {
                $paths[] = str_replace('\\', '/', $drawing->getPath());
            }
        }

        $joined = implode(' | ', $paths);
        $this->assertStringContainsString('favicon.png', $joined);
        $this->assertStringContainsString('ttd.png', $joined);
    }

    public function test_mr_number_keeps_onwj_for_onwj_gudang(): void
    {
        $sheet = $this->makeSheet(
            ['namagudang' => 'ONWJ'],
            '2026-08-04',
            [
                ['label' => 'Bulsak', 'jumlah' => 10, 'sisa_stok' => 11],
            ]
        );

        $this->assertStringContainsString('/ONWJ/', (string) $sheet->getCell('B9')->getValue());
    }

    public function test_keterangan_conditional_yellow_is_removed(): void
    {
        $sheet = $this->makeSheet(
            ['namagudang' => 'ONWJ'],
            '2026-08-04',
            [
                ['label' => 'Item 1', 'jumlah' => 1, 'sisa_stok' => 1],
                ['label' => 'Item 2', 'jumlah' => 2, 'sisa_stok' => 2],
            ]
        );

        foreach (array_keys($sheet->getConditionalStylesCollection()) as $range) {
            $this->assertStringNotContainsString('AX25:AZ29', strtoupper((string) $range));
        }

        $fillType = $sheet->getStyle('AX27')->getFill()->getFillType();
        $this->assertTrue(
            $fillType === \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_NONE
            || $fillType === null
            || $fillType === '',
            'AX27 masih punya fill: '.(string) $fillType
        );
    }

    /**
     * @param  array<string, mixed>  $gudang
     * @param  array<int, array{label: string, jumlah: int, sisa_stok: int}>  $items
     */
    private function makeSheet(array $gudang, string $tanggal, array $items): \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
    {
        $service = new MrExcelExportService($this->templatePath);
        $spreadsheet = $service->makeSpreadsheet($gudang, Carbon::parse($tanggal), $items);

        return $spreadsheet->getSheetByName('MR ONWJ') ?? $spreadsheet->getActiveSheet();
    }
}
