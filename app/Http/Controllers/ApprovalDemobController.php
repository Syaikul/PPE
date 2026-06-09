<?php

namespace App\Http\Controllers;

use App\Models\DemobPengecekan;
use App\Models\Mobilisasi;
use App\Models\MobilisasiPersonel;
use App\Services\BarangVarianService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class ApprovalDemobController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------------------------------------------------------------------
     | LIST approval (gambar 3)
     * ------------------------------------------------------------------- */
    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $posisiMap = $this->fetchPosisiMap();
        $subBarangMap = $this->fetchSubBarangMap();

        $mobilisasiIds = Mobilisasi::where('idgudang', $idgudang)->pluck('id');

        $list = MobilisasiPersonel::with(['personel', 'posisi', 'demobPengecekan'])
            ->whereIn('mobilisasi_id', $mobilisasiIds)
            ->where('demob_status', MobilisasiPersonel::DEMOB_MENUNGGU)
            ->get()
            ->map(function ($mp) use ($personelMapApi, $posisiMap, $subBarangMap) {
                // Hanya item bermasalah (tidak layak / hilang) yang butuh approval.
                $problems = $mp->demobPengecekan
                    ->whereIn('kondisi', [DemobPengecekan::KONDISI_TIDAK_LAYAK, DemobPengecekan::KONDISI_HILANG])
                    ->map(fn ($d) => [
                        'label'   => $subBarangMap[$d->idsubbarang]['label'] ?? 'Item #'.$d->idsubbarang,
                        'kondisi' => $d->kondisi,
                        'catatan' => $d->catatan,
                    ])->values();

                return [
                    'mp'         => $mp,
                    'nama'       => $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id,
                    'posisi_lbl' => $mp->posisi->pluck('idposisi')
                        ->map(fn ($pid) => $posisiMap[$pid]['namaposisi'] ?? 'Posisi #'.$pid)
                        ->implode(' / '),
                    'problems'   => $problems,
                ];
            });

        return view('approval_demob.index', compact('idgudang', 'gudang', 'list'));
    }

    public function approve(Request $request, $idgudang, $personelId)
    {
        $request->validate(['catatan' => 'nullable|string']);

        $mp = $this->findPending($idgudang, $personelId);

        DB::transaction(function () use ($mp, $request) {
            $mp->update([
                'demob_status'     => MobilisasiPersonel::DEMOB_SELESAI,
                'approved_at'      => now(),
                'approval_catatan' => $request->catatan,
            ]);

            $this->maybeCompleteMobilisasi($mp->mobilisasi_id);
        });

        return back()->with('success', 'Approval disetujui. Demob personel selesai.');
    }

    public function reject(Request $request, $idgudang, $personelId)
    {
        $request->validate(['catatan' => 'nullable|string']);

        $mp = $this->findPending($idgudang, $personelId);

        // Kembalikan ke tahap pengecekan agar diperiksa ulang.
        $mp->update([
            'demob_status'     => MobilisasiPersonel::DEMOB_BELUM_CEK,
            'demob_checked_at' => null,
            'approval_catatan' => $request->catatan,
        ]);
        $mp->demobPengecekan()->delete();

        return back()->with('success', 'Approval ditolak. Personel dikembalikan ke tahap pengecekan.');
    }

    private function findPending($idgudang, $personelId): MobilisasiPersonel
    {
        $mobilisasiIds = Mobilisasi::where('idgudang', $idgudang)->pluck('id');

        return MobilisasiPersonel::whereIn('mobilisasi_id', $mobilisasiIds)
            ->where('demob_status', MobilisasiPersonel::DEMOB_MENUNGGU)
            ->findOrFail($personelId);
    }

    private function maybeCompleteMobilisasi(int $mobilisasiId): void
    {
        $mob = Mobilisasi::with('personel')->find($mobilisasiId);
        if (! $mob) {
            return;
        }

        $allSelesai = $mob->personel->isNotEmpty()
            && $mob->personel->every(fn ($mp) => $mp->demob_status === MobilisasiPersonel::DEMOB_SELESAI);

        if ($allSelesai) {
            $mob->update(['status' => 'selesai']);
        }
    }

    private function fetchGudang($idgudang): ?array
    {
        $response = Http::get('http://127.0.0.1:8000/api/gudang');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->firstWhere('idgudang', (int) $idgudang);
    }

    private function fetchPersonelMap(): Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/personel');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->keyBy('idpersonel');
    }

    private function fetchPosisiMap(): Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/posisi');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        return collect($list)->keyBy('idposisi');
    }

    private function fetchSubBarangMap(): Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/barang-with-varian');
        $barangList = $response->successful() ? ($response->json('data') ?? []) : [];

        return BarangVarianService::buildSubBarangMap($barangList);
    }
}
