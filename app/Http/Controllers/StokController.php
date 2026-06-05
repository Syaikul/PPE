<?php

namespace App\Http\Controllers;

use App\Models\Stok;
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

        // Flatten jadi list varian dengan label lengkap: "Coverall - Ukuran L"
        $varianOptions = [];
        foreach ($barangList as $barang) {
            if (!empty($barang['varian'])) {
                foreach ($barang['varian'] as $varian) {
                    $varianOptions[] = [
                        'idvarian'  => $varian['idvarian'],
                        'label'     => $barang['namabarang'] . ' — ' . $varian['namavarian'],
                        'kode'      => $varian['kode_lengkap'] ?? '',
                    ];
                }
            }
        }

        // Ambil stok milik gudang ini, beserta label barang dari varianOptions
        $stokList = Stok::where('idgudang', $idgudang)->latest()->get();
        $varianMap = collect($varianOptions)->keyBy('idvarian');

        return view('stok.index', compact('idgudang', 'gudang', 'varianOptions', 'stokList', 'varianMap'));
    }

    public function store(Request $request, $idgudang)
    {
        $request->validate([
            'idbarangvarian' => 'required|integer',
            'qty'            => 'required|integer|min:1',
        ]);

        Stok::create([
            'idgudang'       => $idgudang,
            'idbarangvarian' => $request->idbarangvarian,
            'qty'            => $request->qty,
        ]);

        return redirect()->route('gudang.stok', $idgudang)
            ->with('success', 'Stok berhasil ditambahkan.');
    }

    public function update(Request $request, $idgudang, $id)
    {
        $request->validate([
            'idbarangvarian' => 'required|integer',
            'qty'            => 'required|integer|min:1',
        ]);

        $stok = Stok::where('idgudang', $idgudang)->findOrFail($id);
        $stok->update([
            'idbarangvarian' => $request->idbarangvarian,
            'qty'            => $request->qty,
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
