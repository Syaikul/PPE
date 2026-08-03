<?php

namespace App\Http\Controllers;

use App\Models\PeminjamanPpe;
use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\PeminjamanPpeService;
use Illuminate\Http\Request;

class PeminjamanPpeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $gudang = MasterApiService::gudangById((int) $idgudang);
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $peminjamanList = PeminjamanPpe::query()
            ->where(function ($q) use ($idgudang) {
                $q->where('idgudang_peminjam', $idgudang)
                    ->orWhere('idgudang_sumber', $idgudang);
            })
            ->latest('tanggal_pengajuan')
            ->latest('id')
            ->get();

        $gudangMap = collect(MasterApiService::gudang())->keyBy('idgudang');

        $stokList = Stok::where('idgudang', $idgudang)->orderBy('id')->get();
        $gudangSumberList = collect(MasterApiService::gudang())
            ->filter(fn ($g) => (int) $g['idgudang'] !== (int) $idgudang)
            ->values()
            ->all();

        return view('peminjaman_ppe.index', compact(
            'idgudang',
            'gudang',
            'peminjamanList',
            'gudangMap',
            'stokList',
            'gudangSumberList',
            'subBarangMap',
            'varianMap'
        ));
    }

    public function store(Request $request, $idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'stok_id'          => 'required|integer',
            'qty'              => 'required|integer|min:1',
            'idgudang_sumber'  => 'required|integer|not_in:'.$idgudang,
            'catatan'          => 'nullable|string',
        ]);

        $stok = Stok::where('idgudang', $idgudang)->findOrFail($request->stok_id);

        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $validation = PeminjamanPpeService::validatePengajuan(
            (int) $idgudang,
            (int) $request->idgudang_sumber,
            $stok->idsubbarang,
            $stok->idbarangvarian,
            (int) $request->qty,
            $subBarangMap,
            $varianMap
        );

        if (! $validation['ok']) {
            return back()->withInput()->with('error', $validation['error']);
        }

        PeminjamanPpe::create([
            'idgudang_peminjam' => $idgudang,
            'idgudang_sumber'   => $request->idgudang_sumber,
            'idsubbarang'       => $stok->idsubbarang,
            'idbarangvarian'    => $stok->idbarangvarian,
            'qty'               => $request->qty,
            'catatan'           => $request->catatan,
            'status'            => PeminjamanPpe::STATUS_PENDING,
            'tanggal_pengajuan' => now()->toDateString(),
        ]);

        return redirect()->route('gudang.peminjaman-ppe', $idgudang)
            ->with('success', 'Pengajuan pinjaman berhasil dikirim.');
    }

    public function approve($idgudang, $id)
    {
        GudangContext::activate((int) $idgudang);

        $peminjaman = PeminjamanPpe::where('idgudang_sumber', $idgudang)->findOrFail($id);

        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        try {
            PeminjamanPpeService::approve($peminjaman, $subBarangMap, $varianMap);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gudang.peminjaman-ppe', $idgudang)
            ->with('success', 'Peminjaman disetujui. Stok telah dipindahkan ke gudang peminjam.');
    }

    public function reject(Request $request, $idgudang, $id)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'catatan_tolak' => 'required|string|max:1000',
        ]);

        $peminjaman = PeminjamanPpe::where('idgudang_sumber', $idgudang)->findOrFail($id);

        try {
            PeminjamanPpeService::reject($peminjaman, $request->catatan_tolak);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gudang.peminjaman-ppe', $idgudang)
            ->with('success', 'Peminjaman ditolak.');
    }

    public function kembalikan($idgudang, $id)
    {
        GudangContext::activate((int) $idgudang);

        $peminjaman = PeminjamanPpe::where('idgudang_sumber', $idgudang)->findOrFail($id);

        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        try {
            PeminjamanPpeService::kembalikan($peminjaman, $subBarangMap, $varianMap);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gudang.peminjaman-ppe', $idgudang)
            ->with('success', 'Barang berhasil dikembalikan ke gudang sumber.');
    }
}
