<?php

namespace App\Http\Controllers;

use App\Models\Mobilisasi;
use App\Models\MobilisasiPengecekan;
use App\Models\MobilisasiPerlengkapan;
use App\Models\MobilisasiPersonel;
use App\Models\MobilisasiPersonelPosisi;
use App\Models\Personel;
use App\Models\PpeKeluar;
use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\PersonelStatusService;
use App\Services\PpeOwnershipService;
use App\Services\StokAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MobilisasiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /* ---------------------------------------------------------------------
     | LIST
     * ------------------------------------------------------------------- */
    public function index($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);

        $mobilisasiList = Mobilisasi::withCount('personel')
            ->where('idgudang', $idgudang)
            ->where('status', '!=', 'selesai')
            ->latest()
            ->get();

        return view('mobilisasi.index', compact('idgudang', 'gudang', 'mobilisasiList'));
    }

    /* ---------------------------------------------------------------------
     | CREATE (form Tambah Mobilisasi - gambar 1)
     * ------------------------------------------------------------------- */
    public function create($idgudang)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $posisiMap = $this->fetchPosisiMap();

        // Personel dengan demob yang belum di-approve (lintas gudang) tidak boleh dimobilisasi lagi.
        $pendingIdpersonel = MobilisasiPersonel::whereIn('demob_status', [
                MobilisasiPersonel::DEMOB_BELUM_CEK,
                MobilisasiPersonel::DEMOB_MENUNGGU,
            ])
            ->whereHas('personel')
            ->with('personel:id,idpersonel')
            ->get()
            ->pluck('personel.idpersonel')
            ->unique()
            ->filter()
            ->all();

        // Hanya personel Offshore — status disinkronkan lintas gudang via idpersonel.
        $personelList = Personel::with('posisi')
            ->where('idgudang', $idgudang)
            ->where('status', 'Offshore')
            ->whereNotIn('idpersonel', $pendingIdpersonel)
            ->get()
            ->map(function ($p) use ($personelMapApi, $posisiMap) {
                $posisiIds = $p->posisi->pluck('idposisi')->all();

                return [
                    'id'         => $p->id,
                    'nama'       => $personelMapApi[$p->idpersonel]['namapersonel'] ?? 'Personel #'.$p->idpersonel,
                    'posisi_ids' => $posisiIds,
                    'posisi_lbl' => collect($posisiIds)
                        ->map(fn ($id) => $posisiMap[$id]['namaposisi'] ?? 'Posisi #'.$id)
                        ->implode(' / '),
                ];
            })
            ->values();

        return view('mobilisasi.create', compact('idgudang', 'gudang', 'personelList', 'posisiMap'));
    }

    public function store(Request $request, $idgudang)
    {
        $request->validate([
            'sr'                 => 'nullable|string|max:255',
            'lokasi_pekerjaan'   => 'nullable|string|max:255',
            'personel'           => 'required|array|min:1',
            'personel.*'         => 'integer',
            'posisi'             => 'array',
        ]);

        $mobilisasi = DB::transaction(function () use ($request, $idgudang) {
            $mobilisasi = Mobilisasi::create([
                'idgudang'         => $idgudang,
                'sr'               => $request->sr,
                'lokasi_pekerjaan' => $request->lokasi_pekerjaan,
                'status'           => 'draft',
            ]);

            $usedPosisi = [];

            foreach ($request->personel as $personelId) {
                $personel = Personel::where('idgudang', $idgudang)
                    ->where('status', 'Offshore')
                    ->find($personelId);
                if (! $personel || PersonelStatusService::hasPendingDemob($personel->idpersonel)) {
                    continue;
                }

                // Posisi yang digunakan untuk mobilisasi ini (dinamis).
                // Default = posisi dari Data Personel, bisa di-override via form.
                $posisiIds = $request->input("posisi.$personelId", []);
                if (empty($posisiIds)) {
                    $posisiIds = $personel->posisi->pluck('idposisi')->all();
                }
                $posisiIds = collect($posisiIds)->map(fn ($v) => (int) $v)->unique()->values();

                $mp = MobilisasiPersonel::create([
                    'mobilisasi_id' => $mobilisasi->id,
                    'personel_id'   => $personel->id,
                ]);

                foreach ($posisiIds as $idposisi) {
                    MobilisasiPersonelPosisi::create([
                        'mobilisasi_personel_id' => $mp->id,
                        'idposisi'               => $idposisi,
                    ]);
                    $usedPosisi[$idposisi] = true;
                }

                // Personel yang dimobilisasi menjadi Onshore di semua gudang.
                PersonelStatusService::syncOnshore($personel->idpersonel);
            }

            // Mandatory selalu di-seed (berlaku untuk semua personel).
            $seedPosisi = array_keys($usedPosisi);
            if ($mandatoryId = $this->mandatoryPosisiId()) {
                $seedPosisi[] = $mandatoryId;
            }

            // Seed Data Perlengkapan dari posisippe untuk posisi yang dipakai + Mandatory.
            $this->seedPerlengkapan($mobilisasi, array_unique($seedPosisi));

            return $mobilisasi;
        });

        return redirect()->route('gudang.mobilisasi.show', [$idgudang, $mobilisasi->id])
            ->with('success', 'Mobilisasi berhasil dibuat.');
    }

    /* ---------------------------------------------------------------------
     | SHOW (detail - tabel personel: Nama | Posisi | Pengecekan Status)
     * ------------------------------------------------------------------- */
    public function show($idgudang, $id)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $posisiMap = $this->fetchPosisiMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);
        $mandatoryId = $this->mandatoryPosisiId();

        $mobilisasi = Mobilisasi::with(['personel.posisi', 'personel.personel', 'personel.pengecekan'])
            ->where('idgudang', $idgudang)
            ->findOrFail($id);

        $allocationByPosisi = $this->allocationByPosisi($mobilisasi);

        $rows = $mobilisasi->personel->map(function ($mp) use ($personelMapApi, $posisiMap, $allocationByPosisi, $kategoriMap, $mandatoryId) {
            $posisiIds = $mp->posisi->pluck('idposisi')->all();
            $expected = $this->expectedItems($posisiIds, $allocationByPosisi, $mandatoryId); // idsubbarang => jumlah

            $this->syncPengecekan($mp, $expected);
            $this->applyAutoKeluarStatus($mp, $expected, $kategoriMap);
            $mp->load('pengecekan');

            $adaCount = $mp->pengecekan->where('status', 'ada')
                ->whereIn('idsubbarang', array_keys($expected))
                ->count();

            $total = count($expected);
            $lengkap = $total > 0 && $adaCount >= $total;

            return [
                'mp'         => $mp,
                'nama'       => $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel->idpersonel,
                'posisi_lbl' => collect($posisiIds)
                    ->map(fn ($pid) => $posisiMap[$pid]['namaposisi'] ?? 'Posisi #'.$pid)
                    ->implode(', '),
                'total'      => $total,
                'ada'        => $adaCount,
                'lengkap'    => $lengkap,
            ];
        });

        $semuaLengkap = $rows->isNotEmpty() && $rows->every(fn ($r) => $r['lengkap']);
        $semuaSubmitted = $mobilisasi->personel->isNotEmpty()
            && $mobilisasi->personel->every(fn ($mp) => $mp->submitted_at !== null);
        $bisaJalankan = $semuaSubmitted && $mobilisasi->status === 'draft';

        return view('mobilisasi.show', compact(
            'idgudang', 'gudang', 'mobilisasi', 'rows', 'semuaLengkap', 'semuaSubmitted', 'bisaJalankan'
        ));
    }

    public function destroy($idgudang, $id)
    {
        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);

        // Kembalikan personel ke Offshore.
        DB::transaction(function () use ($mobilisasi) {
            foreach ($mobilisasi->personel as $mp) {
                $mp->load('personel');
                if ($mp->personel) {
                    PersonelStatusService::syncOffshore($mp->personel->idpersonel);
                }
            }
            $mobilisasi->delete();
        });

        return redirect()->route('gudang.mobilisasi', $idgudang)
            ->with('success', 'Mobilisasi dihapus.');
    }

    /* ---------------------------------------------------------------------
     | DATA PERLENGKAPAN (gambar 2 & 3)
     * ------------------------------------------------------------------- */
    public function perlengkapan($idgudang, $id)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $posisiMap = $this->fetchPosisiMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);

        $mobilisasi = Mobilisasi::with('personel.posisi')->where('idgudang', $idgudang)->findOrFail($id);

        // Posisi yang dipakai di mobilisasi ini + Mandatory (berlaku untuk semua).
        $usedPosisi = $mobilisasi->personel
            ->flatMap(fn ($mp) => $mp->posisi->pluck('idposisi'))
            ->unique()->values();
        if ($mandatoryId = $this->mandatoryPosisiId()) {
            $usedPosisi = $usedPosisi->push($mandatoryId)->unique()->values();
        }

        $items = $mobilisasi->perlengkapan()->get();

        // Perlengkapan dikelompokkan per posisi.
        $perlengkapanByPosisi = $items->where('jenis', 'perlengkapan')->groupBy('idposisi');

        // By request dipisah berdasarkan kategori dari Stok.
        $byRequest = $items->where('jenis', 'by_request');
        $byRequestConsumable = $byRequest->filter(fn ($i) => ($kategoriMap[$i->idsubbarang] ?? 'Non Consumable') === 'Consumable');
        $byRequestNonConsumable = $byRequest->filter(fn ($i) => ($kategoriMap[$i->idsubbarang] ?? 'Non Consumable') !== 'Consumable');

        $subBarangOptions = $subBarangMap->values();

        return view('mobilisasi.perlengkapan', compact(
            'idgudang', 'gudang', 'mobilisasi', 'usedPosisi', 'posisiMap',
            'subBarangMap', 'kategoriMap', 'subBarangOptions',
            'perlengkapanByPosisi', 'byRequestConsumable', 'byRequestNonConsumable'
        ));
    }

    public function storePerlengkapan(Request $request, $idgudang, $id)
    {
        $request->validate([
            'idposisi'    => 'required|integer',
            'idsubbarang' => 'required|integer',
            'qty'         => 'required|integer|min:1',
            'jenis'       => 'required|in:perlengkapan,by_request',
        ]);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);

        MobilisasiPerlengkapan::create([
            'mobilisasi_id' => $mobilisasi->id,
            'idposisi'      => $request->idposisi,
            'idsubbarang'   => $request->idsubbarang,
            'qty'           => $request->qty,
            'jenis'         => $request->jenis,
        ]);

        return back()->with('success', 'Item perlengkapan ditambahkan.');
    }

    public function updatePerlengkapan(Request $request, $idgudang, $id, $itemId)
    {
        $request->validate(['qty' => 'required|integer|min:1']);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $item = $mobilisasi->perlengkapan()->findOrFail($itemId);
        $item->update(['qty' => $request->qty]);

        return back()->with('success', 'Jumlah diperbarui.');
    }

    public function destroyPerlengkapan($idgudang, $id, $itemId)
    {
        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mobilisasi->perlengkapan()->where('id', $itemId)->delete();

        return back()->with('success', 'Item dihapus.');
    }

    /* ---------------------------------------------------------------------
     | PENGECEKAN per personel (gambar 4)
     * ------------------------------------------------------------------- */
    public function pengecekan($idgudang, $id, $personelId)
    {
        session(['idgudang' => $idgudang]);

        $gudang = $this->fetchGudang($idgudang);
        $personelMapApi = $this->fetchPersonelMap();
        $posisiMap = $this->fetchPosisiMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);
        $varianMap = $this->fetchVarianMap();

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with('posisi')
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($personelId);

        // Hitung item yang dialokasikan ke personel ini (union posisi + Mandatory).
        $mandatoryId = $this->mandatoryPosisiId();
        $allocationByPosisi = $this->allocationByPosisi($mobilisasi);
        $expected = $this->expectedItems($mp->posisi->pluck('idposisi')->all(), $allocationByPosisi, $mandatoryId);

        // Sinkronkan ke tabel pengecekan (buat baris yang belum ada, hapus yang tak relevan).
        $this->syncPengecekan($mp, $expected);

        // Auto "Ada" untuk item non-consumable yang sudah dimiliki personel (lintas gudang).
        $this->applyAutoKeluarStatus($mp, $expected, $kategoriMap);

        $pengecekan = $mp->pengecekan()->get()->keyBy('idsubbarang');
        $idpersonel = $mp->personel->idpersonel;

        // Pisah PPE (Non Consumable) vs Consumable.
        $itemsPpe = [];
        $itemsConsumable = [];
        foreach ($expected as $idsub => $jumlah) {
            $isConsumable = ($kategoriMap[$idsub] ?? 'Non Consumable') === 'Consumable';
            $fromKeluar = ! $isConsumable && PpeOwnershipService::owns($idpersonel, (int) $idsub, $jumlah);
            $row = $this->enrichPengecekanRow([
                'idsubbarang'     => $idsub,
                'label'           => $subBarangMap[$idsub]['label'] ?? 'Item #'.$idsub,
                'jumlah'          => $jumlah,
                'status'          => $pengecekan[$idsub]->status ?? 'tidak',
                'catatan'         => $pengecekan[$idsub]->catatan ?? null,
                'from_keluar'     => $fromKeluar,
                'idbarangvarian'  => $pengecekan[$idsub]->idbarangvarian ?? null,
                'varian_label'    => isset($pengecekan[$idsub]->idbarangvarian)
                    ? ($varianMap[$pengecekan[$idsub]->idbarangvarian]['label'] ?? null)
                    : null,
            ], (int) $idsub, $jumlah, $idpersonel, $isConsumable, $subBarangMap, $varianMap, $idgudang);

            if ($isConsumable) {
                $itemsConsumable[] = $row;
            } else {
                $itemsPpe[] = $row;
            }
        }

        $nama = $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id;
        $lengkap = count($expected) > 0 && collect($expected)->keys()->every(
            fn ($idsub) => ($pengecekan[$idsub]->status ?? 'tidak') === 'ada'
        );

        return view('mobilisasi.pengecekan', compact(
            'idgudang', 'gudang', 'mobilisasi', 'mp', 'nama',
            'itemsPpe', 'itemsConsumable', 'lengkap'
        ));
    }

    public function updatePengecekan(Request $request, $idgudang, $id, $personelId)
    {
        $request->validate([
            'idsubbarang'    => 'required|integer',
            'idbarangvarian' => 'nullable|integer',
            'action'         => 'required|in:ada,tidak',
            'catatan'        => 'nullable|string',
        ]);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with('personel')->where('mobilisasi_id', $mobilisasi->id)->findOrFail($personelId);

        $pengecekan = MobilisasiPengecekan::where('mobilisasi_personel_id', $mp->id)
            ->where('idsubbarang', $request->idsubbarang)
            ->firstOrFail();

        $idsub = (int) $request->idsubbarang;
        $idpersonel = $mp->personel->idpersonel;
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);
        $isConsumable = ($kategoriMap[$idsub] ?? 'Non Consumable') === 'Consumable';
        $allowedVarianIds = $subBarangMap[$idsub]['varian_ids'] ?? [];

        // Catat barang keluar hanya saat transisi menjadi "Ada" (hindari duplikasi).
        if ($request->action === 'ada' && $pengecekan->status !== 'ada') {
            $needed = $this->calcIssueQty($idsub, $pengecekan->jumlah, $idpersonel, $isConsumable);

            if ($needed > 0) {
                $idvarian = (int) $request->idbarangvarian;

                if (! $idvarian || ! in_array($idvarian, $allowedVarianIds, true)) {
                    return back()->with('error', 'Pilih varian barang yang akan dikeluarkan.');
                }

                $stokCheck = StokAvailabilityService::checkVarian($idgudang, $idvarian, $needed);
                $varianMap = $this->fetchVarianMap();
                $varianLabel = $varianMap[$idvarian]['label'] ?? 'Varian #'.$idvarian;

                if (! $stokCheck['ok']) {
                    $msg = ! $stokCheck['in_stok']
                        ? "Varian \"{$varianLabel}\" belum ada di stok gudang ini. Tambahkan ke Stok atau buat MR terlebih dahulu."
                        : "Stok varian \"{$varianLabel}\" tidak cukup (tersedia: {$stokCheck['available']}, dibutuhkan: {$needed}). Tambahkan stok atau buat MR terlebih dahulu.";

                    return back()->with('error', $msg);
                }

                DB::transaction(function () use ($idgudang, $idvarian, $needed, $idpersonel, $idsub, $isConsumable, $mp, $mobilisasi, $pengecekan, $request) {
                    StokAvailabilityService::deductVarian($idgudang, $idvarian, $needed);

                    $catatan = $isConsumable ? null : PpeOwnershipService::latestProblemNote($idpersonel, $idsub);

                    PpeKeluar::create([
                        'idgudang'       => $idgudang,
                        'idpersonel'     => $idpersonel,
                        'idsubbarang'    => $idsub,
                        'idbarangvarian' => $idvarian,
                        'qty'            => $needed,
                        'tanggal'        => now()->toDateString(),
                        'catatan'        => $catatan,
                        'personel_id'    => $mp->personel_id,
                        'mobilisasi_id'  => $mobilisasi->id,
                    ]);

                    $pengecekan->update([
                        'status'          => $request->action,
                        'idbarangvarian'  => $idvarian,
                        'catatan'         => $request->catatan,
                    ]);
                });

                return back()->with('success', 'Varian dikeluarkan dari stok dan status PPE diperbarui.');
            }
        }

        $pengecekan->update([
            'status'  => $request->action,
            'catatan' => $request->catatan,
            ...( $request->action === 'tidak' ? ['idbarangvarian' => null] : [] ),
        ]);

        return back()->with('success', 'Status PPE diperbarui.');
    }

    public function submitPersonel($idgudang, $id, $personelId)
    {
        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $mp = MobilisasiPersonel::with('posisi', 'pengecekan')
            ->where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail($personelId);

        $allocationByPosisi = $this->allocationByPosisi($mobilisasi);
        $expected = $this->expectedItems($mp->posisi->pluck('idposisi')->all(), $allocationByPosisi, $this->mandatoryPosisiId());

        $pengecekan = $mp->pengecekan->keyBy('idsubbarang');
        $lengkap = count($expected) > 0 && collect($expected)->keys()->every(
            fn ($idsub) => ($pengecekan[$idsub]->status ?? 'tidak') === 'ada'
        );

        if (! $lengkap) {
            return back()->with('error', 'Belum bisa submit, masih ada PPE yang belum "Ada".');
        }

        $mp->update(['submitted_at' => now()]);

        return back()->with('success', 'Personel berhasil di-submit.');
    }

    public function jalankanProjek($idgudang, $id)
    {
        $mobilisasi = Mobilisasi::with('personel')
            ->where('idgudang', $idgudang)
            ->findOrFail($id);

        if ($mobilisasi->status !== 'draft') {
            return back()->with('error', 'Proyek sudah berjalan atau selesai.');
        }

        $belum = $mobilisasi->personel->filter(fn ($mp) => $mp->submitted_at === null)->count();
        if ($belum > 0) {
            return back()->with('error', 'Semua personel harus menyelesaikan pengecekan terlebih dahulu.');
        }

        $mobilisasi->update(['status' => 'berjalan']);

        return redirect()->route('gudang.demobilisasi', $idgudang)
            ->with('success', 'Proyek berhasil dijalankan. Data tersedia di Demobilisasi.');
    }

    /* ---------------------------------------------------------------------
     | HELPERS
     * ------------------------------------------------------------------- */
    private function seedPerlengkapan(Mobilisasi $mobilisasi, array $posisiIds): void
    {
        if (empty($posisiIds)) {
            return;
        }

        $response = Http::get('http://127.0.0.1:8000/api/posisippe');
        $list = $response->successful() ? ($response->json('data') ?? []) : [];

        foreach ($list as $row) {
            if (! in_array((int) $row['idposisi'], $posisiIds, true)) {
                continue;
            }

            MobilisasiPerlengkapan::create([
                'mobilisasi_id' => $mobilisasi->id,
                'idposisi'      => $row['idposisi'],
                'idsubbarang'   => $row['idsubbarang'],
                'qty'           => $row['qty'] ?? 1,
                'jenis'         => 'perlengkapan',
            ]);
        }
    }

    /** idposisi => [ idsubbarang => jumlah ] (perlengkapan + by_request) */
    private function allocationByPosisi(Mobilisasi $mobilisasi): Collection
    {
        return $mobilisasi->perlengkapan()->get()
            ->groupBy('idposisi')
            ->map(function ($items) {
                $map = [];
                foreach ($items as $item) {
                    $map[$item->idsubbarang] = ($map[$item->idsubbarang] ?? 0) + $item->qty;
                }

                return $map;
            });
    }

    /**
     * Gabungan item dari semua posisi personel + Mandatory: idsubbarang => jumlah.
     * Mandatory berlaku untuk semua personel.
     */
    private function expectedItems(array $posisiIds, Collection $allocationByPosisi, ?int $mandatoryId = null): array
    {
        if ($mandatoryId && ! in_array($mandatoryId, $posisiIds, true)) {
            $posisiIds[] = $mandatoryId;
        }

        $result = [];
        foreach ($posisiIds as $idposisi) {
            $items = $allocationByPosisi->get($idposisi, []);
            foreach ($items as $idsub => $jumlah) {
                // Bila barang sama muncul di >1 posisi, ambil kebutuhan terbesar.
                $result[$idsub] = max($result[$idsub] ?? 0, $jumlah);
            }
        }

        return $result;
    }

    /**
     * Tandai "Ada" otomatis untuk item NON CONSUMABLE yang sudah dimiliki personel
     * (lintas gudang, berdasarkan idpersonel) dan kondisinya masih layak.
     */
    private function applyAutoKeluarStatus(MobilisasiPersonel $mp, array $expected, Collection $kategoriMap): void
    {
        $idpersonel = $mp->personel->idpersonel;

        foreach ($expected as $idsub => $jumlah) {
            if (($kategoriMap[$idsub] ?? 'Non Consumable') === 'Consumable') {
                continue; // consumable tidak dilacak kepemilikan
            }

            if (PpeOwnershipService::owns($idpersonel, (int) $idsub, $jumlah)) {
                MobilisasiPengecekan::where('mobilisasi_personel_id', $mp->id)
                    ->where('idsubbarang', $idsub)
                    ->update(['status' => 'ada']);
            }
        }
    }

    private function mandatoryPosisiId(): ?int
    {
        $posisi = $this->fetchPosisiMap()
            ->first(fn ($p) => strtolower($p['namaposisi'] ?? '') === 'mandatory');

        return isset($posisi['idposisi']) ? (int) $posisi['idposisi'] : null;
    }

    private function calcIssueQty(int $idsub, int $jumlah, int $idpersonel, bool $isConsumable): int
    {
        if ($isConsumable) {
            // Consumable selalu dikeluarkan penuh saat Tambahkan (habis pakai).
            return $jumlah;
        }

        // Non consumable: hanya keluarkan kekurangan dari yang sudah dimiliki (lintas gudang).
        return max(0, $jumlah - PpeOwnershipService::ownedUsableQty($idpersonel, $idsub));
    }

    private function enrichPengecekanRow(array $row, int $idsub, int $jumlah, int $idpersonel, bool $isConsumable, Collection $subBarangMap, Collection $varianMap, int $idgudang): array
    {
        $issueQty = ($row['status'] === 'ada' || ($row['from_keluar'] ?? false))
            ? 0
            : $this->calcIssueQty($idsub, $jumlah, $idpersonel, $isConsumable);

        $varianOptions = $this->buildVarianChoices($idgudang, $idsub, $subBarangMap, $varianMap);

        $row['issue_qty']       = $issueQty;
        $row['varian_options']  = $varianOptions;
        $row['stok_in_table']   = collect($varianOptions)->contains(fn ($v) => $v['in_stok']);
        $row['stok_ok']         = $issueQty <= 0
            || collect($varianOptions)->contains(fn ($v) => $v['stok'] >= $issueQty);
        $row['stok_available']  = collect($varianOptions)->max('stok') ?? 0;

        return $row;
    }

    /** Daftar varian per sub barang beserta stok di gudang. */
    private function buildVarianChoices(int $idgudang, int $idsub, Collection $subBarangMap, Collection $varianMap): array
    {
        $varianIds = $subBarangMap[$idsub]['varian_ids'] ?? [];

        return collect($varianIds)->map(function ($idvarian) use ($idgudang, $varianMap) {
            $stok = StokAvailabilityService::qtyForVarian($idgudang, (int) $idvarian);

            return [
                'idvarian' => (int) $idvarian,
                'label'    => $varianMap[$idvarian]['label'] ?? 'Varian #'.$idvarian,
                'stok'     => $stok,
                'in_stok'  => StokAvailabilityService::varianInStok($idgudang, (int) $idvarian),
            ];
        })->values()->all();
    }

    private function fetchVarianMap(): Collection
    {
        $response = Http::get('http://127.0.0.1:8000/api/barang-with-varian');
        $barangList = $response->successful() ? ($response->json('data') ?? []) : [];

        return BarangVarianService::buildMap($barangList);
    }

    private function syncPengecekan(MobilisasiPersonel $mp, array $expected): void
    {
        $existing = $mp->pengecekan()->pluck('idsubbarang')->all();

        foreach ($expected as $idsub => $jumlah) {
            if (in_array($idsub, $existing, true)) {
                MobilisasiPengecekan::where('mobilisasi_personel_id', $mp->id)
                    ->where('idsubbarang', $idsub)
                    ->update(['jumlah' => $jumlah]);
            } else {
                MobilisasiPengecekan::create([
                    'mobilisasi_personel_id' => $mp->id,
                    'idsubbarang'            => $idsub,
                    'jumlah'                 => $jumlah,
                    'status'                 => 'tidak',
                ]);
            }
        }

        // Hapus baris yang tidak lagi relevan.
        $expectedKeys = array_keys($expected);
        MobilisasiPengecekan::where('mobilisasi_personel_id', $mp->id)
            ->when(! empty($expectedKeys), fn ($q) => $q->whereNotIn('idsubbarang', $expectedKeys))
            ->when(empty($expectedKeys), fn ($q) => $q)
            ->delete();
    }

    /* ----- API fetch helpers ----- */
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

        $stokKategoriByVarian = Stok::where('idgudang', $idgudang)
            ->get()
            ->mapWithKeys(fn ($s) => [$s->idbarangvarian => $s->kategori ?? 'Non Consumable']);

        $kategoriMap = BarangVarianService::buildKategoriMap($barangList, $stokKategoriByVarian);

        return [$subBarangMap, $kategoriMap];
    }
}
