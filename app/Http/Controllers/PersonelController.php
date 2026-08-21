<?php

namespace App\Http\Controllers;

use App\Models\Personel;
use App\Models\PersonelPosisi;
use App\Services\MasterApiService;
use App\Services\PersonelStatusService;
use Illuminate\Http\Request;

class PersonelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $gudang = MasterApiService::gudangById((int) $idgudang);
        $gudangMap = collect(MasterApiService::gudang())->keyBy('idgudang');
        $personelApiList = MasterApiService::personel();
        $posisiList = MasterApiService::posisi();
        $personelMap = collect($personelApiList)->keyBy('idpersonel');
        $posisiMap = collect($posisiList)->keyBy('idposisi');

        $personelList = Personel::with('posisi')
            ->where('idgudang', $idgudang)
            ->latest()
            ->get();

        $registeredPersonelIds = $personelList->pluck('idpersonel')->all();
        $personelApiListTambah = collect($personelApiList)
            ->filter(fn (array $p) => ! in_array((int) $p['idpersonel'], $registeredPersonelIds, true))
            ->values()
            ->all();

        // Onsite dari gudang ini => "Onsite"; dari gudang lain => "Onsite (Nama Gudang)".
        $personelList->each(function ($p) use ($idgudang, $gudangMap) {
            $label = $p->status;

            if ($p->status === PersonelStatusService::STATUS_ONSITE) {
                $asalGudang = PersonelStatusService::activeMobilisasiGudang((int) $p->idpersonel);

                if ($asalGudang && (int) $asalGudang !== (int) $idgudang) {
                    $namaGudang = $gudangMap[$asalGudang]['namagudang'] ?? 'Gudang #'.$asalGudang;
                    $label = 'Onsite ('.$namaGudang.')';
                }
            }

            $p->status_label = $label;
        });

        return view('personel.index', compact(
            'idgudang',
            'gudang',
            'personelApiList',
            'personelApiListTambah',
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
            'status'     => 'required|in:Onsite,Offsite',
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
            'status'     => PersonelStatusService::currentStatus((int) $request->idpersonel),
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
            'status'     => 'required|in:Onsite,Offsite',
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
