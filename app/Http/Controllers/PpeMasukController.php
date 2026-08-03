<?php

namespace App\Http\Controllers;

use App\Models\PermintaanKedatangan;
use App\Services\BarangVarianService;
use App\Services\MasterApiService;

class PpeMasukController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $kedatanganList = PermintaanKedatangan::with(['item.permintaan'])
            ->whereHas('item.permintaan', fn ($q) => $q->where('idgudang', $idgudang))
            ->latest('tanggal')
            ->get();

        return view('ppe_masuk.index', compact('idgudang', 'gudang', 'varianMap', 'subBarangMap', 'kedatanganList'));
    }

    private function fetchGudang($idgudang): ?array
    {
        return MasterApiService::gudangById((int) $idgudang);
    }
}
