<?php

namespace App\Http\Controllers;

use App\Models\PpeKeluar;
use App\Services\BarangVarianService;
use App\Services\MasterApiService;

class PpeKeluarController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

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

        return view('ppe_keluar.index', compact('idgudang', 'gudang', 'keluarList', 'personelMapApi', 'subBarangMap', 'varianMap'));
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
