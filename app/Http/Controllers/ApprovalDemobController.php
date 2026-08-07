<?php

namespace App\Http\Controllers;

use App\Models\DemobPengecekan;
use App\Models\Mobilisasi;
use App\Models\MobilisasiPersonel;
use App\Models\SpareBarangPemakaian;
use App\Services\BarangVarianService;
use App\Services\MasterApiService;
use App\Services\SpareBarangService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
                        'label'          => $subBarangMap[$d->idsubbarang]['label'] ?? 'Item #'.$d->idsubbarang,
                        'kondisi'        => $d->kondisi,
                        'jumlah'         => $d->jumlah,
                        'qty_bermasalah' => $d->qtyBermasalah(),
                        'catatan'        => $d->catatan,
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

        $spareList = $this->pendingSpareList($idgudang, $personelMapApi);

        return view('approval_demob.index', compact('idgudang', 'gudang', 'list', 'spareList'));
    }

    /** Pengajuan pemakaian spare barang yang menunggu approval. */
    private function pendingSpareList($idgudang, Collection $personelMapApi): Collection
    {
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        return SpareBarangPemakaian::with(['item.spareBarang', 'personel'])
            ->where('status', SpareBarangPemakaian::STATUS_MENUNGGU)
            ->whereHas('item.spareBarang', fn ($q) => $q->where('idgudang', $idgudang))
            ->latest('id')
            ->get()
            ->map(fn ($p) => [
                'pemakaian' => $p,
                'no_sr'     => $p->item->spareBarang->no_sr,
                'item_lbl'  => SpareBarangService::labelForItem(
                    $p->item->idsubbarang,
                    $p->item->idbarangvarian,
                    $subBarangMap,
                    $varianMap
                ),
                'nama'      => $p->personel
                    ? ($personelMapApi[$p->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$p->personel_id)
                    : '-',
                'sisa'      => $p->item->sisa,
            ]);
    }

    public function approveSpare(Request $request, $idgudang, $pemakaianId)
    {
        $request->validate(['catatan' => 'nullable|string']);

        $pemakaian = $this->findPendingSpare($idgudang, $pemakaianId);

        try {
            SpareBarangService::approvePemakaian($pemakaian, $request->catatan);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pemakaian spare disetujui. Sisa spare berkurang dan tercatat di PPE Keluar.');
    }

    public function rejectSpare(Request $request, $idgudang, $pemakaianId)
    {
        $request->validate(['catatan' => 'nullable|string']);

        $pemakaian = $this->findPendingSpare($idgudang, $pemakaianId);

        try {
            SpareBarangService::rejectPemakaian($pemakaian, $request->catatan);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pemakaian spare ditolak.');
    }

    private function findPendingSpare($idgudang, $pemakaianId): SpareBarangPemakaian
    {
        return SpareBarangPemakaian::with(['item.spareBarang', 'personel'])
            ->where('status', SpareBarangPemakaian::STATUS_MENUNGGU)
            ->whereHas('item.spareBarang', fn ($q) => $q->where('idgudang', $idgudang))
            ->findOrFail($pemakaianId);
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
        return MasterApiService::gudangById((int) $idgudang);
    }

    private function fetchPersonelMap(): Collection
    {
        return collect(MasterApiService::personel())->keyBy('idpersonel');
    }

    private function fetchPosisiMap(): Collection
    {
        return collect(MasterApiService::posisi())->keyBy('idposisi');
    }

    private function fetchSubBarangMap(): Collection
    {
        return BarangVarianService::buildSubBarangMap(MasterApiService::barangWithVarian());
    }
}
