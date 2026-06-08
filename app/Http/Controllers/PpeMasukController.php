<?php

namespace App\Http\Controllers;

use App\Models\PermintaanKedatangan;
use App\Services\BarangVarianService;
use Illuminate\Support\Facades\Http;

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
        $varianMap = $this->fetchVarianMap();

        $kedatanganList = PermintaanKedatangan::with(['item.permintaan'])
            ->whereHas('item.permintaan', fn ($q) => $q->where('idgudang', $idgudang))
            ->latest('tanggal')
            ->get();

        return view('ppe_masuk.index', compact('idgudang', 'gudang', 'varianMap', 'kedatanganList'));
    }

    private function fetchGudang($idgudang): ?array
    {
        $response = Http::get('http://127.0.0.1:8000/api/gudang');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->firstWhere('idgudang', (int) $idgudang);
    }

    private function fetchVarianMap(): \Illuminate\Support\Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/barang-with-varian');
        $barangList = $response->successful() ? ($response->json('data') ?? []) : [];

        return BarangVarianService::buildMap($barangList);
    }
}
