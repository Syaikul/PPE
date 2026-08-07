<?php

namespace App\Http\Controllers;

use App\Models\Mobilisasi;
use App\Models\MobilisasiPersonel;
use App\Models\SpareBarang;
use App\Models\SpareBarangItem;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\SpareBarangService;
use Illuminate\Http\Request;

/**
 * Spare Barang terikat ke Mobilisasi — dikelola dari halaman Data Perlengkapan MOB.
 * Penanggung jawab & penerima pemakaian harus personel yang ikut mobilisasi tersebut.
 */
class SpareBarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request, $idgudang, $mobilisasiId)
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

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($mobilisasiId);

        if (! $this->isPesertaMob($mobilisasi, (int) $request->personel_id)) {
            return back()->withInput()
                ->with('error', 'Penanggung jawab spare harus personel yang ikut mobilisasi ini.');
        }

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
            $request->items,
            $mobilisasi->id
        );

        return redirect()->route('gudang.mobilisasi.perlengkapan', [$idgudang, $mobilisasi->id])
            ->with('success', 'Spare barang berhasil dibuat. Stok gudang telah dikurangi.');
    }

    public function pakai(Request $request, $idgudang, $mobilisasiId, $srId)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'spare_barang_item_id' => 'required|integer',
            'personel_id'          => 'required|integer',
            'qty'                  => 'required|integer|min:1',
            'catatan'              => 'nullable|string',
        ]);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($mobilisasiId);

        $sr = SpareBarang::where('idgudang', $idgudang)
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($srId);

        $item = SpareBarangItem::where('spare_barang_id', $sr->id)
            ->findOrFail($request->spare_barang_item_id);

        if (! $this->isPesertaMob($mobilisasi, (int) $request->personel_id)) {
            return back()->with('error', 'Penerima spare harus personel yang ikut mobilisasi ini.');
        }

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

        return redirect()->route('gudang.mobilisasi.perlengkapan', [$idgudang, $mobilisasi->id])
            ->with('success', 'Pemakaian spare diajukan. Menunggu persetujuan di Approval Demob.');
    }

    public function kembalikan($idgudang, $mobilisasiId, $srId)
    {
        GudangContext::activate((int) $idgudang);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($mobilisasiId);

        $sr = SpareBarang::with('items')
            ->where('idgudang', $idgudang)
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($srId);

        try {
            $total = SpareBarangService::kembalikan($sr);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gudang.mobilisasi.perlengkapan', [$idgudang, $mobilisasi->id])
            ->with('success', 'Spare barang dikembalikan. '.$total.' item ditambahkan kembali ke stok.');
    }

    private function isPesertaMob(Mobilisasi $mobilisasi, int $personelId): bool
    {
        return MobilisasiPersonel::where('mobilisasi_id', $mobilisasi->id)
            ->where('personel_id', $personelId)
            ->exists();
    }
}
