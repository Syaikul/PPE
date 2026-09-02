<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$htmlPath = __DIR__ . '/PPE-Product-Specification.html';
$pdfPath = __DIR__ . '/PPE-Product-Specification.pdf';

if (! is_file($htmlPath)) {
    fwrite(STDERR, "HTML source not found: {$htmlPath}\n");
    exit(1);
}

$html = file_get_contents($htmlPath);

$pdf = Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
    ->setPaper('a4', 'portrait')
    ->setOption('defaultFont', 'DejaVu Sans')
    ->setOption('isHtml5ParserEnabled', true)
    ->setOption('isRemoteEnabled', false);

$pdf->save($pdfPath);

$size = filesize($pdfPath);
echo "Wrote {$pdfPath} (" . number_format($size) . " bytes)\n";
