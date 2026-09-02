<?php

namespace App\Http\Controllers;

use App\Models\Mobilisasi;
use App\Models\MobilisasiPersonel;
use App\Models\SpareBarang;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\SpareBarangService;
use Illuminate\Http\Request;

/**
 * Spare Barang terikat ke Mobilisasi — dikelola dari halaman Data Perlengkapan MOB.
 * Penanggung jawab harus personel yang ikut mobilisasi tersebut.
 * Sisa dikembalikan ke stok saat demobilisasi; yang terpakai langsung tercatat di PPE Keluar.
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
            'personel_id'     => 'required|integer',
            'items'           => 'required|array|min:1',
            'items.*.stok_id' => 'required|integer',
            'items.*.qty'     => 'required|integer|min:1',
        ]);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($mobilisasiId);

        if ($mobilisasi->hasSubmittedPengecekan()) {
            return back()->with('error', 'Spare barang tidak bisa ditambah karena pengecekan personel sudah disubmit.');
        }

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
            (string) ($mobilisasi->sr ?? ''),
            (int) $request->personel_id,
            $mobilisasi->created_at->toDateString(),
            $request->items,
            $mobilisasi->id
        );

        return redirect()->route('gudang.mobilisasi.perlengkapan', [$idgudang, $mobilisasi->id])
            ->with('success', 'Spare barang berhasil dibuat. Stok gudang telah dikurangi.');
    }

    public function kembalikan(Request $request, $idgudang, $mobilisasiId, $srId)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'sisa'   => 'required|array',
            'sisa.*' => 'required|integer|min:0',
        ]);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($mobilisasiId);

        $sr = SpareBarang::with(['items', 'personel'])
            ->where('idgudang', $idgudang)
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($srId);

        $personelMap = collect(MasterApiService::personel())->keyBy('idpersonel');
        $namaPj = $sr->personel
            ? ($personelMap[$sr->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$sr->personel_id)
            : 'Penanggung jawab';

        $sisaByItemId = collect($request->input('sisa', []))
            ->mapWithKeys(fn ($qty, $itemId) => [(int) $itemId => (int) $qty])
            ->all();

        try {
            $hasil = SpareBarangService::kembalikan($sr, $sisaByItemId, $namaPj);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        $pesan = 'Spare barang diselesaikan.';
        if ($hasil['dikembalikan'] > 0) {
            $pesan .= ' '.$hasil['dikembalikan'].' dikembalikan ke stok.';
        }
        if ($hasil['dipakai'] > 0) {
            $pesan .= ' '.$hasil['dipakai'].' tercatat di PPE Keluar.';
        }

        return back()->with('success', $pesan);
    }

    private function isPesertaMob(Mobilisasi $mobilisasi, int $personelId): bool
    {
        return MobilisasiPersonel::where('mobilisasi_id', $mobilisasi->id)
            ->where('personel_id', $personelId)
            ->exists();
    }
}
