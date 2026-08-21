<?php

namespace App\Http\Controllers;

use App\Models\DashboardNotificationRead;
use App\Models\Mobilisasi;
use App\Models\MobilisasiPersonel;
use App\Models\PeminjamanPpe;
use App\Models\Permintaan;
use App\Models\Personel;
use App\Models\SpareBarangPemakaian;
use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\GudangContext;
use App\Services\MasterApiService;
use App\Services\StokItemService;
use App\Services\StokMinMaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $idgudang = (int) session('idgudang');

        if (! $idgudang || ! MasterApiService::gudangById($idgudang)) {
            return redirect()->route('home')
                ->with('error', 'Pilih gudang terlebih dahulu untuk membuka dashboard.');
        }

        if (! auth()->user()->canAccessGudang($idgudang)) {
            session()->forget(['idgudang', 'namagudang', '_gudang_ctx_id']);

            return redirect()->route('home')
                ->with('error', 'Anda tidak punya akses ke gudang ini. Hubungi SuperAdmin jika perlu ditugaskan.');
        }

        GudangContext::activate($idgudang);

        $gudang = MasterApiService::gudangById($idgudang);
        $gudangMap = collect(MasterApiService::gudang())->keyBy('idgudang');
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        StokMinMaxService::ensureDefaults($idgudang);
        $personelCount = StokMinMaxService::personelCount($idgudang);
        $persenMap = StokMinMaxService::persenMapForGudang($idgudang);
        $stokList = Stok::where('idgudang', $idgudang)->orderBy('qty')->get();

        $stokAlerts = $stokList
            ->map(function (Stok $stok) use ($idgudang, $personelCount, $persenMap, $barangList, $subBarangMap, $varianMap) {
                $metrics = StokMinMaxService::metricsForStok(
                    $stok,
                    $idgudang,
                    $personelCount,
                    $persenMap,
                    $barangList
                );

                return [
                    'id'      => $stok->id,
                    'label'   => StokItemService::labelForRow($stok, $subBarangMap, $varianMap),
                    'kode'    => StokItemService::kodeForRow($stok, $subBarangMap, $varianMap),
                    'qty'     => (int) $stok->qty,
                    'min'     => $metrics['min'],
                    'level'   => $metrics['level'],
                    'badge'   => $metrics['badge'],
                ];
            })
            ->filter(fn (array $item) => in_array($item['level'], [
                StokMinMaxService::LEVEL_RED,
                StokMinMaxService::LEVEL_YELLOW,
            ], true))
            ->values();

        // Tetap muncul setiap refresh selama barang belum dikembalikan.
        $belumDikembalikan = PeminjamanPpe::query()
            ->where('status', PeminjamanPpe::STATUS_APPROVED)
            ->where(function ($query) use ($idgudang) {
                $query->where('idgudang_peminjam', $idgudang)
                    ->orWhere('idgudang_sumber', $idgudang);
            })
            ->latest('tanggal_diterima')
            ->get()
            ->map(fn (PeminjamanPpe $loan) => $this->loanData(
                $loan,
                $idgudang,
                $gudangMap,
                $subBarangMap,
                $varianMap
            ));

        $readKeys = DashboardNotificationRead::where('user_id', auth()->id())
            ->pluck('event_key')
            ->all();

        // Hanya muncul sampai user menutupnya.
        $pengajuanBaru = PeminjamanPpe::query()
            ->where('idgudang_sumber', $idgudang)
            ->where('status', PeminjamanPpe::STATUS_PENDING)
            ->latest('id')
            ->get()
            ->map(function (PeminjamanPpe $loan) use ($idgudang, $gudangMap, $subBarangMap, $varianMap) {
                $data = $this->loanData($loan, $idgudang, $gudangMap, $subBarangMap, $varianMap);
                $data['event_key'] = 'loan:pending:'.$loan->id.':source:'.$idgudang;

                return $data;
            })
            ->reject(fn (array $item) => in_array($item['event_key'], $readKeys, true))
            ->values();

        $hasilPengajuan = PeminjamanPpe::query()
            ->where('idgudang_peminjam', $idgudang)
            ->whereIn('status', [
                PeminjamanPpe::STATUS_APPROVED,
                PeminjamanPpe::STATUS_REJECTED,
            ])
            ->latest('updated_at')
            ->get()
            ->map(function (PeminjamanPpe $loan) use ($idgudang, $gudangMap, $subBarangMap, $varianMap) {
                $data = $this->loanData($loan, $idgudang, $gudangMap, $subBarangMap, $varianMap);
                $data['event_key'] = 'loan:outcome:'.$loan->status.':'.$loan->id.':borrower:'.$idgudang;

                return $data;
            })
            ->reject(fn (array $item) => in_array($item['event_key'], $readKeys, true))
            ->values();

        $mobilisasiAktif = Mobilisasi::where('idgudang', $idgudang)
            ->where('status', '!=', 'selesai')
            ->count();

        $demobMenunggu = MobilisasiPersonel::query()
            ->where('demob_status', MobilisasiPersonel::DEMOB_MENUNGGU)
            ->whereHas('mobilisasi', fn ($query) => $query->where('idgudang', $idgudang))
            ->count();

        $mrBelumSelesai = Permintaan::with('items.kedatangan')
            ->where('idgudang', $idgudang)
            ->get()
            ->filter(fn (Permintaan $permintaan) => $permintaan->status !== 'Sudah Selesai')
            ->count();

        $spareMenunggu = SpareBarangPemakaian::query()
            ->where('status', SpareBarangPemakaian::STATUS_MENUNGGU)
            ->whereHas('item.spareBarang', fn ($query) => $query->where('idgudang', $idgudang))
            ->count();

        $summary = [
            'personel'          => Personel::where('idgudang', $idgudang)->count(),
            'total_stok'        => (int) $stokList->sum('qty'),
            'stok_perlu_atensi' => $stokAlerts->count(),
            'mobilisasi_aktif'  => $mobilisasiAktif,
            'demob_menunggu'    => $demobMenunggu,
            'mr_belum_selesai'  => $mrBelumSelesai,
            'spare_menunggu'    => $spareMenunggu,
        ];

        return view('dashboard.index', compact(
            'idgudang',
            'gudang',
            'summary',
            'stokAlerts',
            'belumDikembalikan',
            'pengajuanBaru',
            'hasilPengajuan'
        ));
    }

    public function dismiss(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_key' => ['required', 'string', 'max:191', 'regex:/^loan:(pending|outcome):/'],
        ]);

        DashboardNotificationRead::updateOrCreate(
            [
                'user_id'   => auth()->id(),
                'event_key' => $validated['event_key'],
            ],
            ['read_at' => now()]
        );

        return response()->json(['ok' => true]);
    }

    private function loanData(
        PeminjamanPpe $loan,
        int $idgudang,
        $gudangMap,
        $subBarangMap,
        $varianMap
    ): array {
        $stok = new Stok([
            'idsubbarang'    => $loan->idsubbarang,
            'idbarangvarian' => $loan->idbarangvarian,
        ]);

        $pihakGudangId = (int) $loan->idgudang_peminjam === $idgudang
            ? (int) $loan->idgudang_sumber
            : (int) $loan->idgudang_peminjam;

        return [
            'id'             => $loan->id,
            'status'         => $loan->status,
            'label'          => StokItemService::labelForRow($stok, $subBarangMap, $varianMap),
            'qty'            => (int) $loan->qty,
            'pihak_gudang'   => $gudangMap[$pihakGudangId]['namagudang'] ?? 'Gudang #'.$pihakGudangId,
            'sebagai'        => (int) $loan->idgudang_peminjam === $idgudang ? 'peminjam' : 'sumber',
            'catatan_tolak'  => $loan->catatan_tolak,
            'tanggal'        => $loan->tanggal_pengajuan?->format('d/m/Y'),
        ];
    }
}
