<?php

namespace App\Http\Controllers;

use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\TransferBarangService;
use Illuminate\Http\Request;

class TransferBarangController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create($idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $gudang = MasterApiService::gudangById((int) $idgudang);
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $stokList = Stok::where('idgudang', $idgudang)
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->get();

        $gudangTujuan = MasterApiService::gudangTransferTujuan();
        $namaGudangTujuan = MasterApiService::gudangTransferTujuanLabel();
        $isGudangTujuan = $gudangTujuan && (int) $gudangTujuan['idgudang'] === (int) $idgudang;
        $gudangList = ($gudangTujuan && ! $isGudangTujuan) ? [$gudangTujuan] : [];

        return view('stok.transfer', compact(
            'idgudang',
            'gudang',
            'stokList',
            'gudangList',
            'gudangTujuan',
            'namaGudangTujuan',
            'isGudangTujuan',
            'subBarangMap',
            'varianMap'
        ));
    }

    public function store(Request $request, $idgudang)
    {
        GudangContext::activate((int) $idgudang);

        $request->validate([
            'idgudang_tujuan' => 'required|integer|not_in:'.$idgudang,
            'tanggal'         => 'required|date',
            'items'           => 'required|array|min:1',
            'items.*.stok_id' => 'required|integer',
            'items.*.qty'     => 'required|integer|min:1',
        ]);

        $idgudangAsal = (int) $idgudang;
        $idgudangTujuan = (int) $request->idgudang_tujuan;

        $namaGudangTujuan = MasterApiService::gudangTransferTujuanLabel();

        if (MasterApiService::isGudangTransferTujuan($idgudangAsal)) {
            return back()->withInput()->with('error', "Transfer barang hanya dapat dilakukan dari gudang lain ke {$namaGudangTujuan}.");
        }

        if (! MasterApiService::isGudangTransferTujuan($idgudangTujuan)) {
            return back()->withInput()->with('error', "Transfer barang hanya dapat dilakukan ke gudang {$namaGudangTujuan}.");
        }

        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $validation = TransferBarangService::validate(
            $idgudangAsal,
            $idgudangTujuan,
            $request->items,
            $subBarangMap,
            $varianMap
        );

        if (! $validation['ok']) {
            return back()->withInput()->with('error', $validation['error']);
        }

        $gudangAsal = MasterApiService::gudangById($idgudangAsal);
        $gudangTujuan = MasterApiService::gudangById($idgudangTujuan);

        TransferBarangService::execute(
            $idgudangAsal,
            $idgudangTujuan,
            $request->tanggal,
            $request->items,
            $gudangAsal['namagudang'] ?? 'Gudang #'.$idgudangAsal,
            $gudangTujuan['namagudang'] ?? 'Gudang #'.$idgudangTujuan
        );

        return redirect()->route('gudang.transfer-barang', $idgudang)
            ->with('success', 'Transfer barang ke '.($gudangTujuan['namagudang'] ?? 'gudang tujuan').' berhasil.');
    }
}
