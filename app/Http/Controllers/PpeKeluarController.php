<?php

namespace App\Http\Controllers;

use App\Models\PpeKeluar;
use App\Services\BarangVarianService;
use Illuminate\Support\Facades\Http;

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
        $subBarangMap = $this->fetchSubBarangMap();

        $keluarList = PpeKeluar::with('personel')
            ->where('idgudang', $idgudang)
            ->latest('tanggal')
            ->latest('id')
            ->get();

        return view('ppe_keluar.index', compact('idgudang', 'gudang', 'keluarList', 'personelMapApi', 'subBarangMap'));
    }

    private function fetchGudang($idgudang): ?array
    {
        $response = Http::get('http://127.0.0.1:8000/api/gudang');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->firstWhere('idgudang', (int) $idgudang);
    }

    private function fetchPersonelMap(): \Illuminate\Support\Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/personel');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->keyBy('idpersonel');
    }

    private function fetchSubBarangMap(): \Illuminate\Support\Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/barang-with-varian');
        $barangList = $response->successful() ? ($response->json('data') ?? []) : [];

        return BarangVarianService::buildSubBarangMap($barangList);
    }
}
