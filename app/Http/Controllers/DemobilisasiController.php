<?php

namespace App\Http\Controllers;

use App\Models\DemobPengecekan;
use App\Models\Mobilisasi;
use App\Models\MobilisasiPersonel;
use App\Models\Personel;
use App\Services\BarangVarianService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

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

        // Personel kembali Offshore (tapi belum bisa dimob lagi sampai demob di-approve).
        Personel::where('id', $mp->personel_id)->update(['status' => 'Offshore']);

        return back()->with('success', 'Personel di-demob. Silakan lakukan pengecekan kelengkapan.');
    }

    /* ---------------------------------------------------------------------
     | DOKUMEN MOBILISASI — item & jumlah yang dibawa personel
     * ------------------------------------------------------------------- */
    public function dokumenMobilisasi($idgudang, $id, $personelId)
    {
        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $posisiMap = $this->fetchPosisiMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with(['posisi', 'personel', 'pengecekan'])
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($personelId);

        $nama = $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id;
        $posisiLbl = $mp->posisi->pluck('idposisi')
            ->map(fn ($pid) => $posisiMap[$pid]['namaposisi'] ?? 'Posisi #'.$pid)
            ->implode(', ');

        $items = $mp->pengecekan
            ->where('status', 'ada')
            ->map(fn ($p) => [
                'label'    => $subBarangMap[$p->idsubbarang]['label'] ?? 'Item #'.$p->idsubbarang,
                'jumlah'   => $p->jumlah,
                'kategori' => $kategoriMap[$p->idsubbarang] ?? 'Non Consumable',
            ])
            ->values();

        return view('demobilisasi.dokumen_mobilisasi', compact(
            'idgudang', 'gudang', 'mobilisasi', 'mp', 'nama', 'posisiLbl', 'items'
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
                'idsubbarang' => $p->idsubbarang,
                'label'       => $subBarangMap[$p->idsubbarang]['label'] ?? 'Item #'.$p->idsubbarang,
                'jumlah'      => $p->jumlah,
                'kondisi'     => $existing[$p->idsubbarang]->kondisi ?? null,
                'catatan'     => $existing[$p->idsubbarang]->catatan ?? null,
            ])
            ->values();

        $readonly = $mp->demob_status !== MobilisasiPersonel::DEMOB_BELUM_CEK;

        return view('demobilisasi.cek_kelengkapan', compact(
            'idgudang', 'gudang', 'mobilisasi', 'mp', 'nama', 'items', 'readonly'
        ));
    }

    public function storeCekKelengkapan(Request $request, $idgudang, $id, $personelId)
    {
        $request->validate([
            'kondisi'   => 'required|array|min:1',
            'kondisi.*' => 'required|in:layak,tidak_layak,hilang',
            'catatan'   => 'array',
        ]);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::where('mobilisasi_id', $mobilisasi->id)->findOrFail($personelId);

        // Catatan wajib untuk kondisi tidak layak / hilang.
        foreach ($request->kondisi as $idsub => $kondisi) {
            $catatan = $request->input("catatan.$idsub");
            if (in_array($kondisi, ['tidak_layak', 'hilang'], true) && blank($catatan)) {
                return back()->withInput()
                    ->with('error', 'Catatan wajib diisi untuk item dengan kondisi Tidak Layak / Hilang.');
            }
        }

        $adaMasalah = false;

        DB::transaction(function () use ($request, $mp, &$adaMasalah) {
            foreach ($request->kondisi as $idsub => $kondisi) {
                DemobPengecekan::updateOrCreate(
                    ['mobilisasi_personel_id' => $mp->id, 'idsubbarang' => (int) $idsub],
                    ['kondisi' => $kondisi, 'catatan' => $request->input("catatan.$idsub")]
                );

                if (in_array($kondisi, ['tidak_layak', 'hilang'], true)) {
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
     | DOKUMEN DEMOBILISASI — hasil pengecekan item
     * ------------------------------------------------------------------- */
    public function dokumenDemobilisasi($idgudang, $id, $personelId)
    {
        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $subBarangMap = $this->fetchSubBarangData($idgudang)[0];

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with(['personel', 'demobPengecekan'])
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($personelId);

        $nama = $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id;

        $items = $mp->demobPengecekan->map(fn ($d) => [
            'label'   => $subBarangMap[$d->idsubbarang]['label'] ?? 'Item #'.$d->idsubbarang,
            'kondisi' => $d->kondisi,
            'catatan' => $d->catatan,
        ])->values();

        return view('demobilisasi.dokumen_demobilisasi', compact(
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

    /** @return array{0: Collection, 1: Collection} [subBarangMap, kategoriMap] */
    private function fetchSubBarangData($idgudang): array
    {
        $response = Http::get('http://127.0.0.1:8000/api/barang-with-varian');
        $barangList = $response->successful() ? ($response->json('data') ?? []) : [];

        $subBarangMap = BarangVarianService::buildSubBarangMap($barangList);

        $stokKategoriByVarian = \App\Models\Stok::where('idgudang', $idgudang)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->idbarangvarian => $s->kategori ?? 'Non Consumable']);

        $kategoriMap = BarangVarianService::buildKategoriMap($barangList, $stokKategoriByVarian);

        return [$subBarangMap, $kategoriMap];
    }
}
