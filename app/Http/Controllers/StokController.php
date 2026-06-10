<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\BarangVarianService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class StokController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        // Simpan gudang aktif ke session agar sidebar bisa pakai
        session(['idgudang' => $idgudang]);

        // Ambil info gudang dari API
        $gudangResponse = Http::get('http://127.0.0.1:8000/api/gudang');
        $gudangList = $gudangResponse->successful() ? ($gudangResponse->json('data') ?? []) : [];
        $gudang = collect($gudangList)->firstWhere('idgudang', (int) $idgudang);

        // Ambil daftar barang + varian dari API untuk dropdown
        $barangResponse = Http::get('http://127.0.0.1:8000/api/barang-with-varian');
        $barangList = $barangResponse->successful() ? ($barangResponse->json('data') ?? []) : [];
        $varianOptions = BarangVarianService::buildOptions($barangList);

        // Tabel stok hanya menampilkan data yang sudah diinput user.
        $stokList = Stok::where('idgudang', $idgudang)->latest()->get();

        $varianMap = collect($varianOptions)->keyBy('idvarian');

        $existingVarianIds = $stokList->pluck('idbarangvarian')->all();
        $varianOptionsTambah = collect($varianOptions)
            ->filter(fn ($v) => ! in_array($v['idvarian'], $existingVarianIds, true))
            ->values()
            ->all();

        return view('stok.index', compact('idgudang', 'gudang', 'varianOptions', 'varianOptionsTambah', 'stokList', 'varianMap'));
    }

    public function store(Request $request, $idgudang)
    {
        $request->validate([
            'idbarangvarian' => 'required|integer',
            'qty'            => 'required|integer|min:1',
            'kategori'       => 'required|in:Consumable,Non Consumable',
        ]);

        $existing = Stok::where('idgudang', $idgudang)
            ->where('idbarangvarian', $request->idbarangvarian)
            ->first();

        if ($existing) {
            return redirect()->route('gudang.stok', $idgudang)
                ->with('error', 'Barang varian ini sudah ada di stok. Penambahan qty hanya melalui Material Request (MR) atau tombol Ubah.');
        }

        Stok::create([
            'idgudang'       => $idgudang,
            'idbarangvarian' => $request->idbarangvarian,
            'qty'            => $request->qty,
            'kategori'       => $request->kategori,
        ]);

        return redirect()->route('gudang.stok', $idgudang)
            ->with('success', 'Stok berhasil ditambahkan.');
    }

    public function update(Request $request, $idgudang, $id)
    {
        $request->validate([
            'idbarangvarian' => 'required|integer',
            'qty'            => 'required|integer|min:1',
            'kategori'       => 'required|in:Consumable,Non Consumable',
        ]);

        $duplicate = Stok::where('idgudang', $idgudang)
            ->where('idbarangvarian', $request->idbarangvarian)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return redirect()->route('gudang.stok', $idgudang)
                ->with('error', 'Barang varian ini sudah ada di stok. Gunakan tombol Ubah pada baris yang sudah ada.');
        }

        $stok = Stok::where('idgudang', $idgudang)->findOrFail($id);
        $stok->update([
            'idbarangvarian' => $request->idbarangvarian,
            'qty'            => $request->qty,
            'kategori'       => $request->kategori,
        ]);

        return redirect()->route('gudang.stok', $idgudang)
            ->with('success', 'Stok berhasil diperbarui.');
    }

    public function destroy($idgudang, $id)
    {
        $stok = Stok::where('idgudang', $idgudang)->findOrFail($id);
        $stok->delete();

        return redirect()->route('gudang.stok', $idgudang)
            ->with('success', 'Stok berhasil dihapus.');
    }
}
