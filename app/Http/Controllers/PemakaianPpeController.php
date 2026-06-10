<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\PpeKeluar;
use App\Services\BarangVarianService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PemakaianPpeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------------------------------------------------------------------
     | MATRIX (gambar 4) — personel x item non-consumable, isi = berapa kali diminta
     * ------------------------------------------------------------------- */
    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);

        // Personel terdaftar di gudang ini.
        $personelList = Personel::where('idgudang', $idgudang)->get();
        $idPersonels = $personelList->pluck('idpersonel')->unique()->values();

        // Pemakaian = riwayat barang keluar (lintas gudang) per idpersonel + item non-consumable.
        $keluar = PpeKeluar::whereIn('idpersonel', $idPersonels)->get()
            ->filter(fn ($k) => ($kategoriMap[$k->idsubbarang] ?? 'Non Consumable') !== 'Consumable');

        // Kolom item = item non-consumable yang pernah keluar.
        $itemIds = $keluar->pluck('idsubbarang')->unique()->values();
        $columns = $itemIds->map(fn ($idsub) => [
            'idsubbarang' => $idsub,
            'label'       => $subBarangMap[$idsub]['label'] ?? 'Item #'.$idsub,
        ]);

        // counts[idpersonel][idsubbarang] = jumlah kali request.
        $counts = $keluar->groupBy('idpersonel')->map(
            fn ($rows) => $rows->groupBy('idsubbarang')->map->count()
        );

        $rows = $personelList->map(fn ($p) => [
            'personel_id' => $p->id,
            'idpersonel'  => $p->idpersonel,
            'nama'        => $personelMapApi[$p->idpersonel]['namapersonel'] ?? 'Personel #'.$p->idpersonel,
            'counts'      => $counts[$p->idpersonel] ?? collect(),
        ])->sortBy('nama')->values();

        return view('pemakaian_ppe.index', compact('idgudang', 'gudang', 'rows', 'columns'));
    }

    /* ---------------------------------------------------------------------
     | DETAIL (gambar 5) — riwayat permintaan per item
     * ------------------------------------------------------------------- */
    public function show($idgudang, $personelId)
    {
        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $posisiMap = $this->fetchPosisiMap();
        $gudangMap = $this->fetchGudangMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);

        $personel = Personel::with('posisi')->where('idgudang', $idgudang)->findOrFail($personelId);
        $nama = $personelMapApi[$personel->idpersonel]['namapersonel'] ?? 'Personel #'.$personel->idpersonel;
        $posisiLbl = $personel->posisi->pluck('idposisi')
            ->map(fn ($pid) => $posisiMap[$pid]['namaposisi'] ?? 'Posisi #'.$pid)
            ->implode(' / ');

        $varianMap = $this->fetchVarianMap();

        // Riwayat keluar (lintas gudang) untuk orang ini, item non-consumable, dikelompokkan per item.
        $keluar = PpeKeluar::where('idpersonel', $personel->idpersonel)
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get()
            ->filter(fn ($k) => ($kategoriMap[$k->idsubbarang] ?? 'Non Consumable') !== 'Consumable');

        $itemHistories = $keluar->groupBy('idsubbarang')->map(function ($rows, $idsub) use ($subBarangMap, $gudangMap, $varianMap) {
            return [
                'idsubbarang' => $idsub,
                'label'       => $subBarangMap[$idsub]['label'] ?? 'Item #'.$idsub,
                'riwayat'     => $rows->values()->map(fn ($r, $i) => [
                    'no'      => $i + 1,
                    'tanggal' => $r->tanggal,
                    'catatan' => $r->catatan,
                    'varian'  => $r->idbarangvarian
                        ? ($varianMap[$r->idbarangvarian]['label'] ?? 'Varian #'.$r->idbarangvarian)
                        : null,
                    'gudang'  => $gudangMap[$r->idgudang]['namagudang'] ?? 'Gudang #'.$r->idgudang,
                ]),
            ];
        })->values();

        return view('pemakaian_ppe.show', compact(
            'idgudang', 'gudang', 'personel', 'nama', 'posisiLbl', 'itemHistories'
        ));
    }

    /* ----- helpers ----- */
    private function fetchGudang($idgudang): ?array
    {
        return $this->fetchGudangMap()->get((int) $idgudang);
    }

    private function fetchGudangMap(): Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/gudang');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->keyBy('idgudang');
    }

    private function fetchPersonelMap(): Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/personel');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->keyBy('idpersonel');
    }

    private function fetchPosisiMap(): Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/posisi');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->keyBy('idposisi');
    }

    /** @return array{0: Collection, 1: Collection} [subBarangMap, kategoriMap] */
    private function fetchSubBarangData($idgudang): array
    {
        $response = Http::get('http://127.0.0.1:8000/api/barang-with-varian');
        $barangList = $response->successful() ? ($response->json('data') ?? []) : [];

        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);

        $stokKategoriByVarian = \App\Models\Stok::where('idgudang', $idgudang)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->idbarangvarian => $s->kategori ?? 'Non Consumable']);

        $kategoriMap = BarangVarianService::buildKategoriMap($barangList, $stokKategoriByVarian);

        return [$subBarangMap, $kategoriMap];
    }

    private function fetchVarianMap(): Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/barang-with-varian');
        $barangList = $response->successful() ? ($response->json('data') ?? []) : [];

        return BarangVarianService::buildMap($barangList);
    }
}
