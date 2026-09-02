<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\PpeKeluar;
use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\PpeKeluarService;
use Illuminate\Http\Request;

class PpeKeluarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $keluarList = PpeKeluar::with('personel')
            ->where('idgudang', $idgudang)
            ->latest('tanggal')
            ->latest('id')
            ->get();

        $ppeOptions = $this->buildPpeOptions((int) $idgudang, $subBarangMap, $varianMap);

        $personelOptions = Personel::where('idgudang', $idgudang)
            ->get()
            ->map(fn ($p) => [
                'id'   => $p->id,
                'nama' => $personelMapApi[$p->idpersonel]['namapersonel'] ?? 'Personel #'.$p->idpersonel,
            ])
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('ppe_keluar.index', compact(
            'idgudang', 'gudang', 'keluarList', 'personelMapApi',
            'subBarangMap', 'varianMap', 'ppeOptions', 'personelOptions'
        ));
    }

    public function store(Request $request, $idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'stok_id'     => 'required|integer',
            'qty'         => 'required|integer|min:1',
            'tanggal'     => 'required|date',
            'personel_id' => 'required|integer',
            'catatan'     => 'nullable|string',
        ]);

        $personel = Personel::where('idgudang', $idgudang)->find($request->personel_id);
        if (! $personel) {
            return back()->withInput()->with('error', 'Penerima harus personel yang terdaftar di gudang ini.');
        }

        try {
            PpeKeluarService::keluarkanManual(
                (int) $idgudang,
                (int) $request->stok_id,
                (int) $request->qty,
                $request->tanggal,
                $personel,
                $request->catatan
            );
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('gudang.ppe-keluar', $idgudang)
            ->with('success', 'Barang keluar berhasil dicatat. Stok gudang telah dikurangi.');
    }

    /**
     * Opsi form: Nama PPE (sub barang) + varian dari stok yang masih ada.
     *
     * @return array<int, array{idsubbarang: int, label: string, varian: array<int, array{stok_id: int, idbarangvarian: int|null, label: string, qty: int}>}>
     */
    private function buildPpeOptions(int $idgudang, $subBarangMap, $varianMap): array
    {
        $stokList = Stok::where('idgudang', $idgudang)
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->get();

        $grouped = [];

        foreach ($stokList as $stok) {
            $idsub = (int) $stok->idsubbarang;
            if ($idsub < 1) {
                continue;
            }

            if (! isset($grouped[$idsub])) {
                $grouped[$idsub] = [
                    'idsubbarang' => $idsub,
                    'label'       => $subBarangMap[$idsub]['label'] ?? 'Item #'.$idsub,
                    'varian'      => [],
                ];
            }

            $grouped[$idsub]['varian'][] = [
                'stok_id'        => $stok->id,
                'idbarangvarian' => $stok->idbarangvarian,
                'label'          => $stok->idbarangvarian
                    ? ($varianMap[$stok->idbarangvarian]['label'] ?? 'Varian #'.$stok->idbarangvarian)
                    : '-',
                'qty'            => (int) $stok->qty,
            ];
        }

        uasort($grouped, fn ($a, $b) => strcasecmp($a['label'], $b['label']));

        return array_values($grouped);
    }

    private function fetchGudang($idgudang): ?array
    {
        return MasterApiService::gudangById((int) $idgudang);
    }

    private function fetchPersonelMap(): \Illuminate\Support\Collection
    {
        return collect(MasterApiService::personel())->keyBy('idpersonel');
    }
}
