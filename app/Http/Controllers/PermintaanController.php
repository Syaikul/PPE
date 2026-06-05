<?php

namespace App\Http\Controllers;

use App\Models\Permintaan;
use App\Models\PermintaanItem;
use App\Models\PermintaanKedatangan;
use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PermintaanController extends Controller
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

        $permintaanList = Permintaan::with(['items.kedatangan'])
            ->where('idgudang', $idgudang)
            ->latest()
            ->get();

        $stokList = Stok::where('idgudang', $idgudang)->get();

        return view('permintaan.index', compact('idgudang', 'gudang', 'varianMap', 'permintaanList', 'stokList'));
    }

    public function store(Request $request, $idgudang)
    {
        $request->validate([
            'nomor_mr'       => 'required|string|max:255',
            'items'          => 'required|array|min:1',
            'items.*.id'     => 'required|integer',
            'items.*.qty'    => 'required|integer|min:1',
        ]);

        $permintaan = Permintaan::create([
            'idgudang'           => $idgudang,
            'nomor_mr'           => $request->nomor_mr,
            'tanggal_permintaan' => now()->toDateString(),
        ]);

        foreach ($request->items as $item) {
            PermintaanItem::create([
                'permintaan_id'   => $permintaan->id,
                'idbarangvarian'  => $item['id'],
                'qty_diminta'     => $item['qty'],
            ]);
        }

        return redirect()->route('gudang.permintaan', $idgudang)
            ->with('success', 'Material Request berhasil dibuat.');
    }

    public function show($idgudang, $id)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $varianMap = $this->fetchVarianMap();

        $permintaan = Permintaan::with(['items.kedatangan'])
            ->where('idgudang', $idgudang)
            ->findOrFail($id);

        return view('permintaan.show', compact('idgudang', 'gudang', 'varianMap', 'permintaan'));
    }

    public function update(Request $request, $idgudang, $id)
    {
        $request->validate([
            'nomor_mr' => 'required|string|max:255',
        ]);

        $permintaan = Permintaan::where('idgudang', $idgudang)->findOrFail($id);
        $permintaan->update(['nomor_mr' => $request->nomor_mr]);

        return redirect()->route('gudang.permintaan', $idgudang)
            ->with('success', 'Nomor MR berhasil diperbarui.');
    }

    public function destroy($idgudang, $id)
    {
        $permintaan = Permintaan::where('idgudang', $idgudang)->findOrFail($id);
        $permintaan->delete();

        return redirect()->route('gudang.permintaan', $idgudang)
            ->with('success', 'Material Request berhasil dihapus.');
    }

    public function storeKedatangan(Request $request, $idgudang, $permintaanId, $itemId)
    {
        $request->validate([
            'tanggal'    => 'required|date',
            'qty_datang' => 'required|integer|min:1',
            'no_po'      => 'nullable|string|max:255',
            'catatan'    => 'nullable|string',
        ]);

        $item = PermintaanItem::whereHas('permintaan', fn ($q) => $q->where('idgudang', $idgudang)->where('id', $permintaanId))
            ->findOrFail($itemId);

        $sisa = $item->qty_diminta - $item->qty_datang;
        if ($request->qty_datang > $sisa) {
            return back()->with('error', 'QTY datang melebihi sisa yang belum datang (' . $sisa . ').');
        }

        PermintaanKedatangan::create([
            'permintaan_item_id' => $item->id,
            'tanggal'            => $request->tanggal,
            'qty_datang'         => $request->qty_datang,
            'no_po'              => $request->no_po,
            'catatan'            => $request->catatan,
        ]);

        return redirect()->route('gudang.permintaan.show', [$idgudang, $permintaanId])
            ->with('success', 'Data kedatangan berhasil ditambahkan.');
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

        $map = [];
        foreach ($barangList as $barang) {
            if (!empty($barang['varian'])) {
                foreach ($barang['varian'] as $varian) {
                    $map[$varian['idvarian']] = [
                        'label' => $barang['namabarang'] . ' ' . $varian['namavarian'],
                        'kode'  => $varian['kode_lengkap'] ?? '',
                    ];
                }
            }
        }

        return collect($map);
    }
}
