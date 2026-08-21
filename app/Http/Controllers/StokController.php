<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\MasterApiService;
use App\Services\StokItemService;
use App\Services\StokMinMaxService;
use Illuminate\Http\Request;

class StokController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $idgudang = (int) $idgudang;
        StokMinMaxService::ensureDefaults($idgudang);

        $gudang = MasterApiService::gudangById($idgudang);
        $barangList = MasterApiService::barangWithVarian();
        $stokOptions = BarangVarianService::buildStokOptions($barangList);
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $stokList = Stok::where('idgudang', $idgudang)->latest()->get();
        $personelCount = StokMinMaxService::personelCount($idgudang);
        $persenMap = StokMinMaxService::persenMapForGudang($idgudang);

        $stokMetrics = $stokList->mapWithKeys(function (Stok $stok) use ($idgudang, $personelCount, $persenMap, $barangList) {
            return [$stok->id => StokMinMaxService::metricsForStok($stok, $idgudang, $personelCount, $persenMap, $barangList)];
        });

        $existingKeys = $stokList->map(fn ($s) => StokItemService::itemKey($s->idsubbarang, $s->idbarangvarian))->all();
        $stokOptionsTambah = collect($stokOptions)
            ->filter(fn ($opt) => ! in_array($opt['key'], $existingKeys, true))
            ->values()
            ->all();

        return view('stok.index', compact(
            'idgudang', 'gudang', 'stokOptions', 'stokOptionsTambah', 'stokList', 'subBarangMap', 'varianMap',
            'personelCount', 'stokMetrics'
        ));
    }

    public function store(Request $request, $idgudang)
    {
        $request->validate([
            'stok_item' => 'required|string',
            'qty'       => 'required|integer|min:1',
            'kategori'  => 'required|in:Consumable,Non Consumable',
        ]);

        $parsed = StokItemService::parseItemKey($request->stok_item);
        $idsubbarang = $parsed['idsubbarang'];
        $idbarangvarian = $parsed['idbarangvarian'];

        if ($idbarangvarian) {
            $barangList = MasterApiService::barangWithVarian();
            foreach (BarangVarianService::buildStokOptions($barangList) as $opt) {
                if ($opt['type'] === 'varian' && (int) $opt['idvarian'] === $idbarangvarian) {
                    $idsubbarang = $opt['idsubbarang'];
                    break;
                }
            }
        }

        if (StokItemService::existsInGudang((int) $idgudang, $idsubbarang, $idbarangvarian)) {
            return redirect()->route('gudang.stok', $idgudang)
                ->with('error', 'Barang ini sudah ada di stok. Penambahan qty hanya melalui Material Request (MR) atau tombol Ubah.');
        }

        if ($idsubbarang) {
            StokMinMaxService::setPersen((int) $idgudang, (int) $idsubbarang, StokMinMaxService::DEFAULT_PERSEN);
        }

        Stok::create([
            'idgudang'       => $idgudang,
            'idsubbarang'    => $idsubbarang,
            'idbarangvarian' => $idbarangvarian,
            'qty'            => $request->qty,
            'kategori'       => $request->kategori,
        ]);

        return redirect()->route('gudang.stok', $idgudang)
            ->with('success', 'Stok berhasil ditambahkan.');
    }

    public function update(Request $request, $idgudang, $id)
    {
        $request->validate([
            'qty'      => 'required|integer|min:1',
            'kategori' => 'required|in:Consumable,Non Consumable',
            'persen'   => 'nullable|numeric|min:0|max:1000',
        ]);

        $stok = Stok::where('idgudang', $idgudang)->findOrFail($id);
        $stok->update([
            'qty'      => $request->qty,
            'kategori' => $request->kategori,
        ]);

        if ($request->filled('persen')) {
            $barangList = MasterApiService::barangWithVarian();
            $idsubbarang = StokMinMaxService::idsubbarangForStok($stok, $barangList);
            if ($idsubbarang) {
                StokMinMaxService::setPersen((int) $idgudang, $idsubbarang, (float) $request->persen);
            }
        }

        return redirect()->route('gudang.stok', $idgudang)
            ->with('success', 'Stok berhasil diperbarui.');
    }

    public function destroy($idgudang, $id)
    {
        $stok = Stok::where('idgudang', $idgudang)->findOrFail($id);
        $stok->delete();

        return redirect()->route('gudang.stok', $idgudang)
            ->with('success', 'Stok berhasil dihapus.');
    }
}
