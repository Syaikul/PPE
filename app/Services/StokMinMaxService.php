<?php

namespace App\Services;

use App\Models\Personel;
use App\Models\Stok;
use App\Models\StokPersen;

class StokMinMaxService
{
    public const DEFAULT_PERSEN = 10.0;

    /** Warna status stok terhadap target Min-Max. */
    public const LEVEL_RED = 'red';

    public const LEVEL_YELLOW = 'yellow';

    public const LEVEL_GRAY = 'gray';

    public const LEVEL_GREEN = 'green';

    public static function personelCount(int $idgudang): int
    {
        return (int) Personel::where('idgudang', $idgudang)->count();
    }

    /** Stok minimum = personel × persen ÷ 100. */
    public static function minQty(int $personelCount, float $persen): int
    {
        if ($personelCount <= 0 || $persen <= 0) {
            return 0;
        }

        return max(1, (int) round($personelCount * $persen / 100));
    }

    /** Stok maksimum ideal = jumlah personel (1 unit per orang). */
    public static function maxQty(int $personelCount): int
    {
        return max(0, $personelCount);
    }

    /**
     * Level warna berdasarkan qty vs Min-Max:
     * - merah   : di bawah Min (0 s.d. Min−1)
     * - kuning  : Min s.d. 2×Min
     * - abu-abu : (2×Min+1) s.d. (personel−1)
     * - hijau   : ≥ jumlah personel (stok penuh)
     *
     * Contoh: 100 personel, Min 10% → Min=10
     * Merah 0–9, Kuning 10–20, Abu-abu 21–99, Hijau ≥100
     */
    public static function level(int $qty, int $min, int $personelCount): string
    {
        if ($personelCount <= 0 || $min <= 0) {
            return self::LEVEL_GRAY;
        }

        $kuningMax = min($min * 2, $personelCount);

        if ($qty < $min) {
            return self::LEVEL_RED;
        }

        if ($qty <= $kuningMax) {
            return self::LEVEL_YELLOW;
        }

        if ($qty >= $personelCount) {
            return self::LEVEL_GREEN;
        }

        return self::LEVEL_GRAY;
    }

    /** @return array{label: string, style: string, color: string} */
    public static function badgeMeta(string $level): array
    {
        $colors = self::levelColors();

        return match ($level) {
            self::LEVEL_RED    => ['label' => 'Kritis',  'style' => self::badgeStyle($colors['red']),    'color' => $colors['red']],
            self::LEVEL_YELLOW => ['label' => 'Menipis', 'style' => self::badgeStyle($colors['yellow']), 'color' => $colors['yellow']],
            self::LEVEL_GREEN  => ['label' => 'Penuh',   'style' => self::badgeStyle($colors['green']),  'color' => $colors['green']],
            default            => ['label' => 'Normal',  'style' => self::badgeStyle($colors['gray']),   'color' => $colors['gray']],
        };
    }

    /** @return array{red: string, yellow: string, gray: string, green: string} */
    public static function levelColors(): array
    {
        return [
            'red'    => '#EF4444',
            'yellow' => '#F59E0B',
            'gray'   => '#64748B',
            'green'  => '#10B981',
        ];
    }

    private static function badgeStyle(string $hex): string
    {
        return 'background-color:'.$hex.';color:#fff';
    }

    /** Legenda untuk popup Informasi Warna. */
    public static function colorLegend(): array
    {
        $c = self::levelColors();

        return [
            [
                'label'      => 'Kritis',
                'color'      => $c['red'],
                'penjelasan' => 'Warna merah menandakan jumlah stok telah mencapai atau berada di bawah batas minimum (Min).',
            ],
            [
                'label'      => 'Menipis',
                'color'      => $c['yellow'],
                'penjelasan' => 'Warna kuning menandakan jumlah stok yang tersedia mendekati batas minimum (Min s.d. 2×Min).',
            ],
            [
                'label'      => 'Normal',
                'color'      => $c['gray'],
                'penjelasan' => 'Warna abu menandakan jumlah stok dalam rentang normal (di atas 2×Min hingga di bawah jumlah personel).',
            ],
            [
                'label'      => 'Penuh',
                'color'      => $c['green'],
                'penjelasan' => 'Warna hijau menandakan jumlah stok melebihi atau sama dengan jumlah personel.',
            ],
        ];
    }

    /** Pastikan semua sub barang master punya baris persen (default 10%) di gudang ini. */
    public static function ensureDefaults(int $idgudang, float $defaultPersen = self::DEFAULT_PERSEN): void
    {
        $barangList = MasterApiService::barangWithVarian();
        $ids = collect(BarangVarianService::buildSubBarangOptions($barangList))
            ->pluck('idsubbarang')
            ->filter()
            ->unique()
            ->values();

        $existing = StokPersen::where('idgudang', $idgudang)
            ->whereIn('idsubbarang', $ids)
            ->pluck('idsubbarang')
            ->all();

        $now = now();
        $rows = [];

        foreach ($ids as $idsubbarang) {
            if (in_array($idsubbarang, $existing, true)) {
                continue;
            }

            $rows[] = [
                'idgudang'    => $idgudang,
                'idsubbarang' => $idsubbarang,
                'persen'      => $defaultPersen,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if ($rows !== []) {
            StokPersen::insert($rows);
        }
    }

    /** @return array<int, float> idsubbarang => persen */
    public static function persenMapForGudang(int $idgudang): array
    {
        return StokPersen::where('idgudang', $idgudang)
            ->pluck('persen', 'idsubbarang')
            ->map(fn ($p) => (float) $p)
            ->all();
    }

    public static function persenFor(int $idgudang, int $idsubbarang): float
    {
        $row = StokPersen::where('idgudang', $idgudang)
            ->where('idsubbarang', $idsubbarang)
            ->first();

        return $row ? (float) $row->persen : self::DEFAULT_PERSEN;
    }

    public static function setPersen(int $idgudang, int $idsubbarang, float $persen): void
    {
        StokPersen::updateOrCreate(
            ['idgudang' => $idgudang, 'idsubbarang' => $idsubbarang],
            ['persen' => $persen]
        );
    }

    /** Ambil idsubbarang dari baris stok (langsung atau lewat varian). */
    public static function idsubbarangForStok(Stok $stok, array $barangList): ?int
    {
        if ($stok->idsubbarang) {
            return (int) $stok->idsubbarang;
        }

        if (! $stok->idbarangvarian) {
            return null;
        }

        foreach ($barangList as $barang) {
            foreach ($barang['sub_barang'] ?? [] as $subBarang) {
                foreach ($subBarang['varian'] ?? [] as $varian) {
                    if ((int) ($varian['idvarian'] ?? 0) === (int) $stok->idbarangvarian) {
                        return (int) $subBarang['idsubbarang'];
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return array{
     *   persen: float,
     *   min: int,
     *   max: int,
     *   level: string,
     *   badge: array{label: string, class: string},
     *   ratio_pct: float|null
     * }
     */
    public static function metricsForStok(
        Stok $stok,
        int $idgudang,
        int $personelCount,
        array $persenMap,
        array $barangList
    ): array {
        $idsubbarang = self::idsubbarangForStok($stok, $barangList);
        $persen = $idsubbarang ? ($persenMap[$idsubbarang] ?? self::DEFAULT_PERSEN) : self::DEFAULT_PERSEN;
        $min = self::minQty($personelCount, $persen);
        $max = self::maxQty($personelCount);
        $level = self::level((int) $stok->qty, $min, $personelCount);

        return [
            'idsubbarang' => $idsubbarang,
            'persen'      => $persen,
            'min'         => $min,
            'max'         => $max,
            'level'       => $level,
            'badge'       => self::badgeMeta($level),
            'ratio_pct'   => $personelCount > 0 ? round(((int) $stok->qty / $personelCount) * 100, 1) : null,
        ];
    }
}
