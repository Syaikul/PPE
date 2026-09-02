<?php

namespace App\Http\Controllers;

use App\Models\DemobPengecekan;
use App\Models\Mobilisasi;
use App\Models\MobilisasiPersonel;
use App\Models\SpareBarang;
use App\Services\BarangVarianService;
use App\Services\MasterApiService;
use App\Services\PersonelStatusService;
use App\Services\SpareBarangService;
use App\Services\StokItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemobilisasiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------------------------------------------------------------------
     | LIST (gambar 1)
     * ------------------------------------------------------------------- */
    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $posisiMap = $this->fetchPosisiMap();

        // Tampilkan mobilisasi yang sudah berjalan & yang selesai (riwayat demob tetap ada).
        $mobilisasiList = Mobilisasi::with(['personel.posisi', 'personel.personel'])
            ->where('idgudang', $idgudang)
            ->whereIn('status', ['berjalan', 'selesai'])
            ->latest()
            ->get()
            ->map(function ($mob) use ($personelMapApi, $posisiMap) {
                $mob->rows = $mob->personel->map(fn ($mp) => [
                    'mp'         => $mp,
                    'nama'       => $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id,
                    'posisi_lbl' => $mp->posisi->pluck('idposisi')
                        ->map(fn ($pid) => $posisiMap[$pid]['namaposisi'] ?? 'Posisi #'.$pid)
                        ->implode(', '),
                ]);

                return $mob;
            });

        return view('demobilisasi.index', compact('idgudang', 'gudang', 'mobilisasiList'));
    }

    /* ---------------------------------------------------------------------
     | SELESAIKAN — personel OffSite, mulai proses demob
     * ------------------------------------------------------------------- */
    public function selesaikan($idgudang, $id, $personelId)
    {
        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::where('mobilisasi_id', $mobilisasi->id)->findOrFail($personelId);

        $mp->update([
            'demob_status'  => MobilisasiPersonel::DEMOB_BELUM_CEK,
            'tanggal_demob' => now()->toDateString(),
        ]);

        // Personel kembali Offsite di semua gudang (belum bisa dimob lagi sampai demob di-approve).
        $mp->load('personel');
        if ($mp->personel) {
            PersonelStatusService::syncOffsite($mp->personel->idpersonel);
        }

        return back()->with('success', 'Personel di-demob. Silakan lakukan pengecekan kelengkapan.');
    }

    /* ---------------------------------------------------------------------
     | DOKUMEN MOB / DEMOB — satu dokumen; bagian demob muncul setelah cek selesai
     * ------------------------------------------------------------------- */
    public function dokumenMobilisasi($idgudang, $id, $personelId)
    {
        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $posisiMap = $this->fetchPosisiMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);
        $varianMap = $this->fetchVarianMapFromApi();

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with(['posisi', 'personel', 'pengecekan', 'demobPengecekan'])
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($personelId);

        $nama = $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id;
        $posisiLbl = $mp->posisi->pluck('idposisi')
            ->map(fn ($pid) => $posisiMap[$pid]['namaposisi'] ?? 'Posisi #'.$pid)
            ->implode(', ');

        $items = $mp->pengecekan
            ->where('status', 'ada')
            ->map(fn ($p) => [
                'label'        => $subBarangMap[$p->idsubbarang]['label'] ?? 'Item #'.$p->idsubbarang,
                'varian_label' => $p->idbarangvarian
                    ? ($varianMap[$p->idbarangvarian]['label'] ?? 'Varian #'.$p->idbarangvarian)
                    : null,
                'jumlah'       => $p->jumlah,
                'kategori'     => $kategoriMap[$p->idsubbarang] ?? 'Non Consumable',
            ])
            ->values();

        $includeDemob = in_array($mp->demob_status, [
            MobilisasiPersonel::DEMOB_MENUNGGU,
            MobilisasiPersonel::DEMOB_SELESAI,
        ], true);

        $demobItems = $includeDemob
            ? $mp->demobPengecekan->map(fn ($d) => [
                'label'          => $subBarangMap[$d->idsubbarang]['label'] ?? 'Item #'.$d->idsubbarang,
                'kondisi'        => $d->kondisi,
                'jumlah'         => $d->jumlah,
                'qty_bermasalah' => $d->isBermasalah() ? $d->qtyBermasalah() : null,
                'catatan'        => $d->catatan,
            ])->values()
            : collect();

        return view('demobilisasi.dokumen_mobilisasi', compact(
            'idgudang', 'gudang', 'mobilisasi', 'mp', 'nama', 'posisiLbl',
            'items', 'includeDemob', 'demobItems'
        ));
    }

    /* ---------------------------------------------------------------------
     | CEK KELENGKAPAN (gambar 2) — inspeksi item non-consumable
     * ------------------------------------------------------------------- */
    public function cekKelengkapan($idgudang, $id, $personelId)
    {
        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with(['personel', 'pengecekan', 'demobPengecekan'])
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($personelId);

        $nama = $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id;
        $existing = $mp->demobPengecekan->keyBy('idsubbarang');

        // Hanya item Non Consumable yang dibawa (status 'ada' saat mob).
        $items = $mp->pengecekan
            ->where('status', 'ada')
            ->filter(fn ($p) => ($kategoriMap[$p->idsubbarang] ?? 'Non Consumable') !== 'Consumable')
            ->map(fn ($p) => [
                'idsubbarang'    => $p->idsubbarang,
                'label'          => $subBarangMap[$p->idsubbarang]['label'] ?? 'Item #'.$p->idsubbarang,
                'jumlah'         => $p->jumlah,
                'kondisi'        => $existing[$p->idsubbarang]->kondisi ?? null,
                'qty_bermasalah' => $existing[$p->idsubbarang]->qty_bermasalah ?? null,
                'catatan'        => $existing[$p->idsubbarang]->catatan ?? null,
            ])
            ->values();

        $readonly = $mp->demob_status !== MobilisasiPersonel::DEMOB_BELUM_CEK;

        return view('demobilisasi.cek_kelengkapan', compact(
            'idgudang', 'gudang', 'mobilisasi', 'mp', 'nama', 'items', 'readonly'
        ));
    }

    /* ---------------------------------------------------------------------
     | CEK SPARE BARANG — kembalikan / pakai spare setelah personel OffSite
     * ------------------------------------------------------------------- */
    public function cekSpare($idgudang, $id, $personelId)
    {
        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $mobilisasi = Mobilisasi::with(['personel.personel'])
            ->where('idgudang', $idgudang)
            ->findOrFail($id);

        $mp = MobilisasiPersonel::with('personel')
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($personelId);

        if ($mp->demob_status === null) {
            return redirect()->route('gudang.demobilisasi', $idgudang)
                ->with('error', 'Pengecekan spare baru bisa dilakukan setelah personel diselesaikan (OffSite).');
        }

        $nama = $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id;

        $mobPersonelOptions = $mobilisasi->personel->map(fn ($p) => [
            'mp_id'       => $p->id,
            'personel_id' => $p->personel_id,
            'nama'        => $personelMapApi[$p->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$p->personel_id,
        ])->sortBy('nama')->values();

        $srList = SpareBarang::with(['items', 'personel'])
            ->where('idgudang', $idgudang)
            ->where('mobilisasi_id', $mobilisasi->id)
            ->latest('tanggal')
            ->latest('id')
            ->get();

        $showActions = true;

        return view('demobilisasi.cek_spare', compact(
            'idgudang', 'gudang', 'mobilisasi', 'mp', 'nama',
            'srList', 'mobPersonelOptions', 'subBarangMap', 'varianMap',
            'showActions'
        ));
    }

    public function storeCekKelengkapan(Request $request, $idgudang, $id, $personelId)
    {
        $request->validate([
            'kondisi'          => 'required|array|min:1',
            'kondisi.*'        => 'required|in:layak,tidak_layak,hilang',
            'qty_bermasalah'   => 'array',
            'qty_bermasalah.*' => 'nullable|integer|min:1',
            'catatan'          => 'array',
        ]);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with('pengecekan')->where('mobilisasi_id', $mobilisasi->id)->findOrFail($personelId);

        // Jumlah yang dibawa saat MOB per item (batas atas jumlah bermasalah).
        $jumlahByIdsub = $mp->pengecekan->keyBy('idsubbarang')->map(fn ($p) => (int) $p->jumlah);

        // Catatan wajib untuk kondisi tidak layak / hilang.
        foreach ($request->kondisi as $idsub => $kondisi) {
            $catatan = $request->input("catatan.$idsub");
            if (in_array($kondisi, ['tidak_layak', 'hilang'], true) && blank($catatan)) {
                return back()->withInput()
                    ->with('error', 'Catatan wajib diisi untuk item dengan kondisi Tidak Layak / Hilang.');
            }
        }

        $adaMasalah = false;

        DB::transaction(function () use ($request, $mp, $jumlahByIdsub, &$adaMasalah) {
            foreach ($request->kondisi as $idsub => $kondisi) {
                $jumlah = max(1, $jumlahByIdsub[(int) $idsub] ?? 1);
                $bermasalah = in_array($kondisi, ['tidak_layak', 'hilang'], true);

                // Berapa unit yang rusak/hilang; sisanya dianggap masih layak.
                $qtyBermasalah = null;
                if ($bermasalah) {
                    $qtyBermasalah = (int) ($request->input("qty_bermasalah.$idsub") ?: $jumlah);
                    $qtyBermasalah = min(max(1, $qtyBermasalah), $jumlah);
                }

                DemobPengecekan::updateOrCreate(
                    ['mobilisasi_personel_id' => $mp->id, 'idsubbarang' => (int) $idsub],
                    [
                        'jumlah'         => $jumlah,
                        'kondisi'        => $kondisi,
                        'qty_bermasalah' => $qtyBermasalah,
                        'catatan'        => $request->input("catatan.$idsub"),
                    ]
                );

                if ($bermasalah) {
                    $adaMasalah = true;
                }
            }

            // Tanpa masalah => langsung Selesai; ada masalah => Menunggu Approval.
            $mp->update([
                'demob_status'     => $adaMasalah ? MobilisasiPersonel::DEMOB_MENUNGGU : MobilisasiPersonel::DEMOB_SELESAI,
                'demob_checked_at' => now(),
                'approved_at'      => $adaMasalah ? null : now(),
            ]);

            $this->maybeCompleteMobilisasi($mp->mobilisasi_id);
        });

        $msg = $adaMasalah
            ? 'Pengecekan tersimpan. Item bermasalah menunggu approval.'
            : 'Pengecekan tersimpan. Demob personel selesai.';

        return redirect()->route('gudang.demobilisasi', $idgudang)->with('success', $msg);
    }

    /* ---------------------------------------------------------------------
     | DOKUMEN DEMOBILISASI — dialihkan ke dokumen gabungan
     * ------------------------------------------------------------------- */
    public function dokumenDemobilisasi($idgudang, $id, $personelId)
    {
        return redirect()->route('gudang.demobilisasi.dokumen-mob', [$idgudang, $id, $personelId]);
    }

    /* ---------------------------------------------------------------------
     | DOKUMEN SPARE BARANG — alokasi, sisa, dan yang terpakai
     * ------------------------------------------------------------------- */
    public function dokumenSpare($idgudang, $id, $personelId)
    {
        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $barangList = MasterApiService::barangWithVarian();
        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);
        $varianMap = BarangVarianService::buildMap($barangList);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with('personel')
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($personelId);

        $nama = $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id;

        $srList = SpareBarang::with(['items', 'personel'])
            ->where('idgudang', $idgudang)
            ->where('mobilisasi_id', $mobilisasi->id)
            ->latest('tanggal')
            ->latest('id')
            ->get();

        $items = $srList->flatMap(function ($sr) use ($subBarangMap, $varianMap, $personelMapApi, $mobilisasi) {
            $pjNama = $sr->personel
                ? ($personelMapApi[$sr->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$sr->personel_id)
                : '-';

            return $sr->items->map(function ($item) use ($sr, $subBarangMap, $varianMap, $pjNama, $mobilisasi) {
                return [
                    'sr'       => $mobilisasi->sr ?: ($sr->no_sr ?: '-'),
                    'label'    => SpareBarangService::labelForItem(
                        $item->idsubbarang, $item->idbarangvarian, $subBarangMap, $varianMap
                    ),
                    'jumlah'   => $item->jumlah,
                    'sisa'     => $item->sisa,
                    'dipakai'  => $item->qtyDipakai(),
                    'returned' => $item->isReturned(),
                    'pj'       => $pjNama,
                ];
            });
        })->values();

        return view('demobilisasi.dokumen_spare', compact(
            'idgudang', 'gudang', 'mobilisasi', 'mp', 'nama', 'items'
        ));
    }

    /* ---------------------------------------------------------------------
     | HELPERS
     * ------------------------------------------------------------------- */
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

    /** @return array{0: Collection, 1: Collection} [subBarangMap, kategoriMap] */
    private function fetchSubBarangData($idgudang): array
    {
        return StokItemService::buildSubBarangKategoriData((int) $idgudang, MasterApiService::barangWithVarian());
    }

    private function fetchVarianMapFromApi(): Collection
    {
        return BarangVarianService::buildMap(MasterApiService::barangWithVarian());
    }
}
