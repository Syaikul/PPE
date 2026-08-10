<?php

namespace App\Http\Controllers;

use App\Services\MasterSyncService;
use Illuminate\Http\Request;

class MasterSyncController extends Controller
{
    public function index()
    {
        return view('master_sync.index', [
            'status'  => MasterSyncService::status(),
            'baseUrl' => MasterSyncService::baseUrl(),
        ]);
    }

    public function sync(Request $request)
    {
        $endpoint = $request->input('endpoint');

        $hasil = ($endpoint && array_key_exists($endpoint, MasterSyncService::ENDPOINTS))
            ? [MasterSyncService::syncOne($endpoint)]
            : MasterSyncService::syncAll();

        $gagal = array_values(array_filter($hasil, fn (array $r) => ! $r['ok']));
        $sukses = array_values(array_filter($hasil, fn (array $r) => $r['ok']));

        if ($gagal === []) {
            $ringkas = implode(', ', array_map(fn (array $r) => $r['label'].' ('.$r['jumlah'].')', $sukses));

            return redirect()->route('master.sync')->with('success', 'Sync berhasil: '.$ringkas.'.');
        }

        $pesan = collect($gagal)->map(fn (array $r) => $r['label'].': '.$r['error'])->implode(' | ');

        return redirect()->route('master.sync')
            ->with('error', 'Sebagian data gagal di-sync, data lama tetap dipakai. '.$pesan);
    }
}
