<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\MrExcelExportService;
use App\Services\StokItemService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use RuntimeException;

class PermintaanPpeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Halaman Buat Tabel Permintaan (gambar 1). */
    public function create($idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $gudang = $this->fetchGudang($idgudang);
        $barangList = $this->fetchBarangList();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);
        $stokList = Stok::where('idgudang', $idgudang)->orderBy('id')->get()
            ->sortBy(fn (Stok $stok) => mb_strtolower(
                StokItemService::labelForRow($stok, $subBarangMap, $varianMap),
                'UTF-8'
            ))
            ->values();

        return view('permintaan.create_table', compact('idgudang', 'gudang', 'varianMap', 'subBarangMap', 'stokList'));
    }

    /** Submit → download Excel template, tanpa simpan ke data permintaan. */
    public function export(Request $request, $idgudang, MrExcelExportService $excelService)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'tanggal_permintaan'=> 'required|date',
            'items'             => 'required|array|min:1',
            'items.*.stok_id'   => 'required|integer',
            'items.*.qty'       => 'required|integer|min:1',
        ]);

        $gudang = $this->fetchGudang($idgudang);
        if (! $gudang) {
            return back()->with('error', 'Data gudang tidak ditemukan.');
        }

        $barangList = $this->fetchBarangList();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $excelItems = [];
        foreach ($request->items as $item) {
            $stok = Stok::where('idgudang', $idgudang)->findOrFail($item['stok_id']);

            $excelItems[] = [
                'label'     => StokItemService::labelForRow($stok, $subBarangMap, $varianMap),
                'jumlah'    => (int) $item['qty'],
                'sisa_stok' => (int) $stok->qty,
            ];
        }

        try {
            return $excelService->download(
                $gudang,
                Carbon::parse($request->tanggal_permintaan),
                $excelItems
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function fetchGudang($idgudang): ?array
    {
        return MasterApiService::gudangById((int) $idgudang);
    }

    private function fetchBarangList(): array
    {
        return MasterApiService::barangWithVarian();
    }

    private function fetchVarianMap(): \Illuminate\Support\Collection
    {
        return BarangVarianService::buildMap($this->fetchBarangList());
    }
}
