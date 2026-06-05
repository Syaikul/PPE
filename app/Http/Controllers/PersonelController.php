<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\PersonelPosisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PersonelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $gudangResponse = Http::get('http://127.0.0.1:8000/api/gudang');
        $gudangList = $gudangResponse->successful() ? ($gudangResponse->json('data') ?? []) : [];
        $gudang = collect($gudangList)->firstWhere('idgudang', (int) $idgudang);

        $personelResponse = Http::get('http://127.0.0.1:8000/api/personel');
        $personelApiList = $personelResponse->successful() ? ($personelResponse->json('data') ?? []) : [];
        $personelMap = collect($personelApiList)->keyBy('idpersonel');

        $posisiResponse = Http::get('http://127.0.0.1:8000/api/posisi');
        $posisiList = $posisiResponse->successful() ? ($posisiResponse->json('data') ?? []) : [];
        $posisiMap = collect($posisiList)->keyBy('idposisi');

        $personelList = Personel::with('posisi')
            ->where('idgudang', $idgudang)
            ->latest()
            ->get();

        return view('personel.index', compact(
            'idgudang',
            'gudang',
            'personelApiList',
            'personelMap',
            'posisiList',
            'posisiMap',
            'personelList'
        ));
    }

    public function store(Request $request, $idgudang)
    {
        $request->validate([
            'idpersonel' => 'required|integer',
            'status'     => 'required|in:Onshore,Offshore',
            'idposisi'   => 'required|array|min:1',
            'idposisi.*' => 'integer',
        ]);

        $exists = Personel::where('idgudang', $idgudang)
            ->where('idpersonel', $request->idpersonel)
            ->exists();

        if ($exists) {
            return redirect()->route('gudang.personel', $idgudang)
                ->with('error', 'Personel ini sudah terdaftar di gudang ini.');
        }

        $personel = Personel::create([
            'idgudang'   => $idgudang,
            'idpersonel' => $request->idpersonel,
            'status'     => $request->status,
        ]);

        foreach (array_unique($request->idposisi) as $idposisi) {
            PersonelPosisi::create([
                'personel_id' => $personel->id,
                'idposisi'    => $idposisi,
            ]);
        }

        return redirect()->route('gudang.personel', $idgudang)
            ->with('success', 'Personel berhasil ditambahkan.');
    }

    public function update(Request $request, $idgudang, $id)
    {
        $request->validate([
            'status'     => 'required|in:Onshore,Offshore',
            'idposisi'   => 'required|array|min:1',
            'idposisi.*' => 'integer',
        ]);

        $personel = Personel::where('idgudang', $idgudang)->findOrFail($id);
        $personel->update(['status' => $request->status]);

        $personel->posisi()->delete();
        foreach (array_unique($request->idposisi) as $idposisi) {
            PersonelPosisi::create([
                'personel_id' => $personel->id,
                'idposisi'    => $idposisi,
            ]);
        }

        return redirect()->route('gudang.personel', $idgudang)
            ->with('success', 'Personel berhasil diperbarui.');
    }

    public function destroy($idgudang, $id)
    {
        $personel = Personel::where('idgudang', $idgudang)->findOrFail($id);
        $personel->delete();

        return redirect()->route('gudang.personel', $idgudang)
            ->with('success', 'Personel berhasil dihapus.');
    }
}
