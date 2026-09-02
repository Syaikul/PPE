<?php

namespace Tests\Unit;

use App\Support\SimpleZipFile;
use PHPUnit\Framework\TestCase;

class SimpleZipFileTest extends TestCase
{
    public function test_reads_xlsx_template_without_ext_zip(): void
    {
        $path = dirname(__DIR__, 2).'/resources/templates/mr-ppe-iwes.xlsx';
        if (! is_file($path)) {
            $this->markTestSkipped('Template Excel permintaan tidak ditemukan.');
        }

        $zip = new SimpleZipFile();
        $this->assertTrue($zip->open($path));

        $rels = $zip->contents('_rels/.rels');
        $this->assertIsString($rels);
        $this->assertStringContainsString('workbook.xml', $rels);

        $shared = $zip->contents('xl/sharedStrings.xml');
        $this->assertIsString($shared);
        $this->assertStringContainsString('SURAT PERMINTAAN BARANG', $shared);

        $sheet = $zip->contents('xl/worksheets/sheet1.xml');
        $this->assertIsString($sheet);
        $this->assertStringContainsString('sheetData', $sheet);

        $zip->close();
    }
}
