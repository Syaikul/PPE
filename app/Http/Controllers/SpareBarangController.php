<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\SpareBarang;
use App\Models\SpareBarangItem;
use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\SpareBarangService;
use Illuminate\Http\Request;

class SpareBarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $gudang = MasterApiService::gudangById((int) $idgudang);
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);
        $personelMapApi = collect(MasterApiService::personel())->keyBy('idpersonel');

        $stokList = Stok::where('idgudang', $idgudang)
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->get();

        $personelList = Personel::where('idgudang', $idgudang)->get()
            ->map(fn ($p) => [
                'id'   => $p->id,
                'nama' => $personelMapApi[$p->idpersonel]['namapersonel'] ?? 'Personel #'.$p->idpersonel,
            ])
            ->sortBy('nama')
            ->values();

        $srList = SpareBarang::with(['items.pemakaian', 'personel'])
            ->where('idgudang', $idgudang)
            ->latest('tanggal')
            ->latest('id')
            ->get();

        return view('spare_barang.index', compact(
            'idgudang',
            'gudang',
            'stokList',
            'personelList',
            'personelMapApi',
            'srList',
            'subBarangMap',
            'varianMap'
        ));
    }

    public function store(Request $request, $idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'no_sr'           => 'required|string|max:255',
            'tanggal'         => 'required|date',
            'personel_id'     => 'required|integer',
            'items'           => 'required|array|min:1',
            'items.*.stok_id' => 'required|integer',
            'items.*.qty'     => 'required|integer|min:1',
        ]);

        Personel::where('idgudang', $idgudang)->findOrFail($request->personel_id);

        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $validation = SpareBarangService::validateSr((int) $idgudang, $request->items, $subBarangMap, $varianMap);

        if (! $validation['ok']) {
            return back()->withInput()->with('error', $validation['error']);
        }

        SpareBarangService::createSr(
            (int) $idgudang,
            $request->no_sr,
            (int) $request->personel_id,
            $request->tanggal,
            $request->items
        );

        return redirect()->route('gudang.spare-barang', $idgudang)
            ->with('success', 'Spare barang berhasil dibuat. Stok gudang telah dikurangi.');
    }

    public function pakai(Request $request, $idgudang, $id)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'spare_barang_item_id' => 'required|integer',
            'personel_id'          => 'required|integer',
            'qty'                  => 'required|integer|min:1',
            'catatan'              => 'nullable|string',
        ]);

        $sr = SpareBarang::where('idgudang', $idgudang)->findOrFail($id);

        $item = SpareBarangItem::where('spare_barang_id', $sr->id)
            ->findOrFail($request->spare_barang_item_id);

        Personel::where('idgudang', $idgudang)->findOrFail($request->personel_id);

        try {
            SpareBarangService::ajukanPemakaian(
                $item,
                (int) $request->personel_id,
                (int) $request->qty,
                $request->catatan
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gudang.spare-barang', $idgudang)
            ->with('success', 'Pemakaian spare diajukan. Menunggu persetujuan di Approval Demob.');
    }

    public function kembalikan($idgudang, $id)
    {
        GudangContext::activate((int) $idgudang);

        $sr = SpareBarang::with('items')->where('idgudang', $idgudang)->findOrFail($id);

        try {
            $total = SpareBarangService::kembalikan($sr);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gudang.spare-barang', $idgudang)
            ->with('success', 'Spare barang dikembalikan. '.$total.' item ditambahkan kembali ke stok.');
    }
}
