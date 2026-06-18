<?php

namespace App\Http\Controllers;

use App\Models\Permintaan;
use App\Models\PermintaanItem;
use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MrPdfExportService;
use App\Services\StokItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

    /** Submit → simpan MR + download PDF template. */
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

        $permintaan = Permintaan::create([
            'idgudang'           => $idgudang,
            'nomor_mr'           => $request->nomor_mr,
            'tanggal_permintaan' => $request->tanggal_permintaan,
        ]);

        foreach ($request->items as $item) {
            $stok = Stok::where('idgudang', $idgudang)->findOrFail($item['stok_id']);

            PermintaanItem::create([
                'permintaan_id'  => $permintaan->id,
                'idsubbarang'    => $stok->idsubbarang,
                'idbarangvarian' => $stok->idbarangvarian,
                'qty_diminta'    => $item['qty'],
            ]);
        }

        $tanggalFormatted = \Carbon\Carbon::parse($request->tanggal_permintaan)->format('d/m/Y');

        return $pdfService->download($gudang, $request->nomor_mr, $tanggalFormatted, $pdfItems);
    }

    private function fetchGudang($idgudang): ?array
    {
        $response = Http::get('http://127.0.0.1:8000/api/gudang');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->firstWhere('idgudang', (int) $idgudang);
    }

    private function fetchBarangList(): array
    {
        $response = Http::get('http://127.0.0.1:8000/api/barang-with-varian');

        return $response->successful() ? ($response->json('data') ?? []) : [];
    }

    private function fetchVarianMap(): \Illuminate\Support\Collection
    {
        return BarangVarianService::buildMap($this->fetchBarangList());
    }
}
