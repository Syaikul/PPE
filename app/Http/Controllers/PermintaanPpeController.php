<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\MrPdfExportService;
use App\Services\StokItemService;
use Illuminate\Http\Request;

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
        $stokList = Stok::where('idgudang', $idgudang)->orderBy('id')->get();

        return view('permintaan.create_table', compact('idgudang', 'gudang', 'varianMap', 'subBarangMap', 'stokList'));
    }

    /** Submit → download PDF template saja, tanpa simpan ke data permintaan. */
    public function export(Request $request, $idgudang, MrPdfExportService $pdfService)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'nomor_mr'          => 'required|string|max:255',
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

        $pdfItems = [];
        foreach ($request->items as $item) {
            $stok = Stok::where('idgudang', $idgudang)->findOrFail($item['stok_id']);

            $pdfItems[] = [
                'label'     => StokItemService::labelForRow($stok, $subBarangMap, $varianMap),
                'jumlah'    => (int) $item['qty'],
                'satuan'    => 'Pcs',
                'sisa_stok' => (int) $stok->qty,
                'kategori'  => $stok->kategori ?? Stok::KATEGORI_NON_CONSUMABLE,
            ];
        }

        $tanggalFormatted = \Carbon\Carbon::parse($request->tanggal_permintaan)->format('d/m/Y');

        return $pdfService->download($gudang, $request->nomor_mr, $tanggalFormatted, $pdfItems);
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
