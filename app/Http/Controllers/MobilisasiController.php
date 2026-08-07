<?php

namespace App\Http\Controllers;

use App\Models\Mobilisasi;
use App\Models\MobilisasiPengecekan;
use App\Models\MobilisasiPerlengkapan;
use App\Models\MobilisasiPersonel;
use App\Models\MobilisasiPersonelPosisi;
use App\Models\Personel;
use App\Models\PpeKeluar;
use App\Models\SpareBarang;
use App\Models\Stok;
use App\Services\BarangVarianService;
use App\Services\MasterApiService;
use App\Services\PersonelStatusService;
use App\Services\PpeOwnershipService;
use App\Services\StokAvailabilityService;
use App\Services\StokItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

        // Hanya personel Offsite — status disinkronkan lintas gudang via idpersonel.
        $personelList = Personel::with('posisi')
            ->where('idgudang', $idgudang)
            ->where('status', PersonelStatusService::STATUS_OFFSITE)
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
                    ->where('status', PersonelStatusService::STATUS_OFFSITE)
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

                // Personel yang dimobilisasi menjadi Onsite di semua gudang.
                PersonelStatusService::syncOnsite($personel->idpersonel);
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
        $byRequestByMp = $this->byRequestByPersonel($mobilisasi);

        $rows = $mobilisasi->personel->map(function ($mp) use ($personelMapApi, $posisiMap, $allocationByPosisi, $byRequestByMp, $kategoriMap, $mandatoryId) {
            $posisiIds = $mp->posisi->pluck('idposisi')->all();
            $expected = $this->expectedItemsFor($mp, $allocationByPosisi, $byRequestByMp, $mandatoryId); // idsubbarang => jumlah

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

        // Kembalikan personel ke Offsite.
        DB::transaction(function () use ($mobilisasi) {
            foreach ($mobilisasi->personel as $mp) {
                $mp->load('personel');
                if ($mp->personel) {
                    PersonelStatusService::syncOffsite($mp->personel->idpersonel);
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
        $personelMapApi = $this->fetchPersonelMap();
        [$subBarangMap, $kategoriMap] = $this->fetchSubBarangData($idgudang);
        $varianMap = $this->fetchVarianMap();

        $mobilisasi = Mobilisasi::with(['personel.posisi', 'personel.personel'])
            ->where('idgudang', $idgudang)
            ->findOrFail($id);

        // Posisi yang dipakai di mobilisasi ini + Mandatory (berlaku untuk semua).
        $usedPosisi = $mobilisasi->personel
            ->flatMap(fn ($mp) => $mp->posisi->pluck('idposisi'))
            ->unique()->values();
        if ($mandatoryId = $this->mandatoryPosisiId()) {
            $usedPosisi = $usedPosisi->push($mandatoryId)->unique()->values();
        }

        // Personel peserta MOB — pilihan "Yang Mengajukan" by request & penanggung jawab spare.
        $mobPersonelOptions = $mobilisasi->personel->map(fn ($mp) => [
            'mp_id'       => $mp->id,
            'personel_id' => $mp->personel_id,
            'nama'        => $personelMapApi[$mp->personel->idpersonel]['namapersonel'] ?? 'Personel #'.$mp->personel_id,
        ])->sortBy('nama')->values();

        $mpNamaById = $mobPersonelOptions->keyBy('mp_id');
        $namaUserLabel = 'USER ('.($gudang['namagudang'] ?? 'Gudang #'.$idgudang).')';

        $items = $mobilisasi->perlengkapan()->get();

        // Perlengkapan dikelompokkan per posisi.
        $perlengkapanByPosisi = $items->where('jenis', 'perlengkapan')->groupBy('idposisi');

        // By request dipisah berdasarkan kategori dari Stok.
        $byRequest = $items->where('jenis', 'by_request')->map(function ($i) use ($mpNamaById, $posisiMap, $namaUserLabel) {
            if ($i->untuk_user) {
                $i->pengaju_label = $namaUserLabel;
            } elseif ($i->mobilisasi_personel_id) {
                $i->pengaju_label = $mpNamaById[$i->mobilisasi_personel_id]['nama'] ?? 'Personel MOB #'.$i->mobilisasi_personel_id;
            } else {
                // Data lama (by request per posisi).
                $i->pengaju_label = $posisiMap[$i->idposisi]['namaposisi'] ?? 'Posisi #'.$i->idposisi;
            }

            return $i;
        });
        $byRequestConsumable = $byRequest->filter(fn ($i) => ($kategoriMap[$i->idsubbarang] ?? 'Non Consumable') === 'Consumable');
        $byRequestNonConsumable = $byRequest->filter(fn ($i) => ($kategoriMap[$i->idsubbarang] ?? 'Non Consumable') !== 'Consumable');

        $subBarangOptions = $subBarangMap->values();

        // Peta varian per sub barang untuk request "User" (perlu pilih varian saat keluar stok).
        $varianBySubBarang = $subBarangMap->map(function ($sub) use ($varianMap) {
            return [
                'has_variants' => (bool) ($sub['has_real_variants'] ?? false),
                'varians'      => collect($sub['varian_ids'] ?? [])->map(fn ($idv) => [
                    'id'    => (int) $idv,
                    'label' => $varianMap[$idv]['label'] ?? 'Varian #'.$idv,
                ])->values()->all(),
            ];
        });

        // ===== Spare Barang untuk mobilisasi ini =====
        $stokList = Stok::where('idgudang', $idgudang)
            ->where('qty', '>', 0)
            ->orderBy('id')
            ->get();

        $srList = SpareBarang::with(['items.pemakaian', 'personel'])
            ->where('idgudang', $idgudang)
            ->where('mobilisasi_id', $mobilisasi->id)
            ->latest('tanggal')
            ->latest('id')
            ->get();

        return view('mobilisasi.perlengkapan', compact(
            'idgudang', 'gudang', 'mobilisasi', 'usedPosisi', 'posisiMap',
            'subBarangMap', 'kategoriMap', 'subBarangOptions', 'varianMap',
            'perlengkapanByPosisi', 'byRequestConsumable', 'byRequestNonConsumable',
            'mobPersonelOptions', 'namaUserLabel', 'varianBySubBarang',
            'stokList', 'srList'
        ));
    }

    public function storePerlengkapan(Request $request, $idgudang, $id)
    {
        $request->validate([
            'idsubbarang'    => 'required|integer',
            'qty'            => 'required|integer|min:1',
            'jenis'          => 'required|in:perlengkapan,by_request',
            'idposisi'       => 'required_if:jenis,perlengkapan|nullable|integer',
            'penerima'       => 'required_if:jenis,by_request|nullable|string',
            'idbarangvarian' => 'nullable|integer',
        ]);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);

        if ($request->jenis === 'perlengkapan') {
            MobilisasiPerlengkapan::create([
                'mobilisasi_id' => $mobilisasi->id,
                'idposisi'      => $request->idposisi,
                'idsubbarang'   => $request->idsubbarang,
                'qty'           => $request->qty,
                'jenis'         => 'perlengkapan',
            ]);

            return back()->with('success', 'Item perlengkapan ditambahkan.');
        }

        // ===== By Request =====
        if ($request->penerima === 'user') {
            // Untuk klien: langsung keluar stok atas nama USER (Nama Gudang), dianggap habis.
            return $this->storeByRequestUser($request, (int) $idgudang, $mobilisasi);
        }

        $mp = MobilisasiPersonel::where('mobilisasi_id', $mobilisasi->id)
            ->findOrFail((int) $request->penerima);

        MobilisasiPerlengkapan::create([
            'mobilisasi_id'          => $mobilisasi->id,
            'idposisi'               => null,
            'mobilisasi_personel_id' => $mp->id,
            'idsubbarang'            => $request->idsubbarang,
            'qty'                    => $request->qty,
            'jenis'                  => 'by_request',
        ]);

        return back()->with('success', 'Item by request ditambahkan untuk personel. Barang dikeluarkan saat pengecekan personel tersebut.');
    }

    /** By Request untuk "User" (klien): langsung potong stok + catat PPE Keluar, dianggap habis/hilang. */
    private function storeByRequestUser(Request $request, int $idgudang, Mobilisasi $mobilisasi)
    {
        $idsub = (int) $request->idsubbarang;
        $qty = (int) $request->qty;

        [$subBarangMap] = $this->fetchSubBarangData($idgudang);
        $sub = $subBarangMap[$idsub] ?? null;
        if (! $sub) {
            return back()->with('error', 'Barang tidak ditemukan.');
        }

        $gudang = $this->fetchGudang($idgudang);
        $namaUser = 'USER ('.($gudang['namagudang'] ?? 'Gudang #'.$idgudang).')';
        $subLabel = $sub['label'] ?? 'Sub Barang #'.$idsub;

        $hasRealVariants = $sub['has_real_variants'] ?? false;
        $allowedVarianIds = $sub['varian_ids'] ?? [];
        $legacyVarianIds = $sub['all_varian_ids'] ?? [];

        $idvarian = null;

        if ($hasRealVariants) {
            $idvarian = (int) $request->idbarangvarian;

            if (! $idvarian || ! in_array($idvarian, $allowedVarianIds, true)) {
                return back()->with('error', 'Pilih varian barang yang akan dikeluarkan untuk User.');
            }

            $stokCheck = StokAvailabilityService::checkVarian($idgudang, $idvarian, $qty);
        } else {
            $stokCheck = StokAvailabilityService::checkSubBarang($idgudang, $idsub, $legacyVarianIds, $qty);
        }

        if (! $stokCheck['ok']) {
            $msg = ! $stokCheck['in_stok']
                ? "Barang \"{$subLabel}\" belum ada di stok gudang ini. Tambahkan ke Stok atau buat MR terlebih dahulu."
                : "Stok \"{$subLabel}\" tidak cukup (tersedia: {$stokCheck['available']}, dibutuhkan: {$qty}).";

            return back()->with('error', $msg);
        }

        DB::transaction(function () use ($idgudang, $idsub, $idvarian, $legacyVarianIds, $hasRealVariants, $qty, $mobilisasi, $namaUser) {
            if ($hasRealVariants) {
                StokAvailabilityService::deductVarian($idgudang, $idvarian, $qty);
            } else {
                StokAvailabilityService::deductSubBarang($idgudang, $idsub, $legacyVarianIds, $qty);
            }

            MobilisasiPerlengkapan::create([
                'mobilisasi_id' => $mobilisasi->id,
                'idposisi'      => null,
                'idsubbarang'   => $idsub,
                'qty'           => $qty,
                'jenis'         => 'by_request',
                'untuk_user'    => true,
            ]);

            PpeKeluar::create([
                'idgudang'       => $idgudang,
                'idpersonel'     => null,
                'idsubbarang'    => $idsub,
                'idbarangvarian' => $idvarian,
                'qty'            => $qty,
                'tanggal'        => now()->toDateString(),
                'catatan'        => $namaUser,
                'personel_id'    => null,
                'mobilisasi_id'  => $mobilisasi->id,
            ]);
        });

        return back()->with('success', "Barang dikeluarkan dari stok atas nama {$namaUser} dan tercatat di PPE Keluar.");
    }

    public function updatePerlengkapan(Request $request, $idgudang, $id, $itemId)
    {
        $request->validate(['qty' => 'required|integer|min:1']);

        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $item = $mobilisasi->perlengkapan()->findOrFail($itemId);

        if ($item->untuk_user) {
            return back()->with('error', 'Item By Request untuk User sudah dikeluarkan dari stok dan tidak bisa diubah.');
        }

        $item->update(['qty' => $request->qty]);

        return back()->with('success', 'Jumlah diperbarui.');
    }

    public function destroyPerlengkapan($idgudang, $id, $itemId)
    {
        $mobilisasi = Mobilisasi::where('idgudang', $idgudang)->findOrFail($id);
        $item = $mobilisasi->perlengkapan()->findOrFail($itemId);

        if ($item->untuk_user) {
            return back()->with('error', 'Item By Request untuk User sudah dikeluarkan dari stok dan tidak bisa dihapus.');
        }

        $item->delete();

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

        // Hitung item yang dialokasikan ke personel ini (union posisi + Mandatory + by request pribadi).
        $mandatoryId = $this->mandatoryPosisiId();
        $allocationByPosisi = $this->allocationByPosisi($mobilisasi);
        $byRequestByMp = $this->byRequestByPersonel($mobilisasi);
        $expected = $this->expectedItemsFor($mp, $allocationByPosisi, $byRequestByMp, $mandatoryId);

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
                    : ((($pengecekan[$idsub]->status ?? '') === 'ada')
                        ? ($subBarangMap[$idsub]['label'] ?? null)
                        : null),
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
        $legacyVarianIds = $subBarangMap[$idsub]['all_varian_ids'] ?? [];
        $hasRealVariants = $subBarangMap[$idsub]['has_real_variants'] ?? ! empty($allowedVarianIds);

        // Catat barang keluar hanya saat transisi menjadi "Ada" (hindari duplikasi).
        if ($request->action === 'ada' && $pengecekan->status !== 'ada') {
            $needed = $this->calcIssueQty($idsub, $pengecekan->jumlah, $idpersonel, $isConsumable);

            if ($needed > 0) {
                if ($hasRealVariants) {
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
                            'status'         => $request->action,
                            'idbarangvarian' => $idvarian,
                            'catatan'        => $request->catatan,
                        ]);
                    });

                    return back()->with('success', 'Varian dikeluarkan dari stok dan status PPE diperbarui.');
                }

                $subLabel = $subBarangMap[$idsub]['label'] ?? 'Sub Barang #'.$idsub;
                $stokCheck = StokAvailabilityService::checkSubBarang($idgudang, $idsub, $legacyVarianIds, $needed);

                if (! $stokCheck['ok']) {
                    $msg = ! $stokCheck['in_stok']
                        ? "Barang \"{$subLabel}\" belum ada di stok gudang ini. Tambahkan ke Stok atau buat MR terlebih dahulu."
                        : "Stok \"{$subLabel}\" tidak cukup (tersedia: {$stokCheck['available']}, dibutuhkan: {$needed}). Tambahkan stok atau buat MR terlebih dahulu.";

                    return back()->with('error', $msg);
                }

                DB::transaction(function () use ($idgudang, $idsub, $legacyVarianIds, $needed, $idpersonel, $isConsumable, $mp, $mobilisasi, $pengecekan, $request) {
                    StokAvailabilityService::deductSubBarang($idgudang, $idsub, $legacyVarianIds, $needed);

                    $catatan = $isConsumable ? null : PpeOwnershipService::latestProblemNote($idpersonel, $idsub);

                    PpeKeluar::create([
                        'idgudang'       => $idgudang,
                        'idpersonel'     => $idpersonel,
                        'idsubbarang'    => $idsub,
                        'idbarangvarian' => null,
                        'qty'            => $needed,
                        'tanggal'        => now()->toDateString(),
                        'catatan'        => $catatan,
                        'personel_id'    => $mp->personel_id,
                        'mobilisasi_id'  => $mobilisasi->id,
                    ]);

                    $pengecekan->update([
                        'status'         => $request->action,
                        'idbarangvarian' => null,
                        'catatan'        => $request->catatan,
                    ]);
                });

                return back()->with('success', 'Barang dikeluarkan dari stok dan status PPE diperbarui.');
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
        $byRequestByMp = $this->byRequestByPersonel($mobilisasi);
        $expected = $this->expectedItemsFor($mp, $allocationByPosisi, $byRequestByMp, $this->mandatoryPosisiId());

        $pengecekan = $mp->pengecekan->keyBy('idsubbarang');
        $lengkap = count($expected) > 0 && collect($expected)->keys()->every(
            fn ($idsub) => ($pengecekan[$idsub]->status ?? 'tidak') === 'ada'
        );

        if (! $lengkap) {
            return back()->with('error', 'Belum bisa submit, masih ada PPE yang belum "Ada".');
        }

        $mp->update(['submitted_at' => now()]);

        // Setelah submit pengecekan, langsung kembali ke halaman Mobilisasi.
        return redirect()->route('gudang.mobilisasi.show', [$idgudang, $mobilisasi->id])
            ->with('success', 'Pengecekan personel berhasil di-submit.');
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

        $list = MasterApiService::posisiPpe();

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

    /** idposisi => [ idsubbarang => jumlah ] (hanya jenis perlengkapan). */
    private function allocationByPosisi(Mobilisasi $mobilisasi): Collection
    {
        return $mobilisasi->perlengkapan()
            ->where('jenis', 'perlengkapan')
            ->get()
            ->groupBy('idposisi')
            ->map(function ($items) {
                $map = [];
                foreach ($items as $item) {
                    $map[$item->idsubbarang] = ($map[$item->idsubbarang] ?? 0) + $item->qty;
                }

                return $map;
            });
    }

    /** mobilisasi_personel_id => [ idsubbarang => qty ] untuk by request per personel. */
    private function byRequestByPersonel(Mobilisasi $mobilisasi): Collection
    {
        return $mobilisasi->perlengkapan()
            ->where('jenis', 'by_request')
            ->where('untuk_user', false)
            ->whereNotNull('mobilisasi_personel_id')
            ->get()
            ->groupBy('mobilisasi_personel_id')
            ->map(function ($items) {
                $map = [];
                foreach ($items as $item) {
                    $map[$item->idsubbarang] = ($map[$item->idsubbarang] ?? 0) + $item->qty;
                }

                return $map;
            });
    }

    /**
     * Item yang diharapkan untuk satu personel:
     * union posisi + Mandatory (ambil terbesar), lalu DITAMBAH by request pribadinya.
     */
    private function expectedItemsFor(MobilisasiPersonel $mp, Collection $allocationByPosisi, Collection $byRequestByMp, ?int $mandatoryId = null): array
    {
        $expected = $this->expectedItems($mp->posisi->pluck('idposisi')->all(), $allocationByPosisi, $mandatoryId);

        foreach ($byRequestByMp->get($mp->id, []) as $idsub => $qty) {
            $expected[$idsub] = ($expected[$idsub] ?? 0) + $qty;
        }

        return $expected;
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
        $sub = $subBarangMap[$idsub] ?? null;
        if (! $sub) {
            return [];
        }

        if (! ($sub['has_real_variants'] ?? false)) {
            $legacyIds = $sub['all_varian_ids'] ?? [];
            $stok = StokAvailabilityService::qtyForSubBarang($idgudang, $idsub, $legacyIds);

            return [[
                'idvarian'     => null,
                'is_sub_level' => true,
                'label'        => $sub['label'] ?? 'Sub Barang #'.$idsub,
                'stok'         => $stok,
                'in_stok'      => StokAvailabilityService::subInStok($idgudang, $idsub, $legacyIds),
            ]];
        }

        $varianIds = $sub['varian_ids'] ?? [];

        return collect($varianIds)->map(function ($idvarian) use ($idgudang, $varianMap) {
            $stok = StokAvailabilityService::qtyForVarian($idgudang, (int) $idvarian);

            return [
                'idvarian'     => (int) $idvarian,
                'is_sub_level' => false,
                'label'        => $varianMap[$idvarian]['label'] ?? 'Varian #'.$idvarian,
                'stok'         => $stok,
                'in_stok'      => StokAvailabilityService::varianInStok($idgudang, (int) $idvarian),
            ];
        })->values()->all();
    }

    private function fetchVarianMap(): Collection
    {
        return BarangVarianService::buildMap(MasterApiService::barangWithVarian());
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
}
