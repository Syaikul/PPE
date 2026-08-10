<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    // Sync manual data master (gudang, personel, posisi, barang, posisippe)
    Route::get('/master-sync', [App\Http\Controllers\MasterSyncController::class, 'index'])->name('master.sync');
    Route::post('/master-sync', [App\Http\Controllers\MasterSyncController::class, 'sync'])->name('master.sync.run');

    Route::get('/gudang/{idgudang}/stok', [App\Http\Controllers\StokController::class, 'index'])->name('gudang.stok');
    Route::post('/gudang/{idgudang}/stok', [App\Http\Controllers\StokController::class, 'store'])->name('gudang.stok.store');
    Route::put('/gudang/{idgudang}/stok/{id}', [App\Http\Controllers\StokController::class, 'update'])->name('gudang.stok.update');
    Route::delete('/gudang/{idgudang}/stok/{id}', [App\Http\Controllers\StokController::class, 'destroy'])->name('gudang.stok.destroy');

    Route::get('/gudang/{idgudang}/transfer-barang', [App\Http\Controllers\TransferBarangController::class, 'create'])->name('gudang.transfer-barang');
    Route::post('/gudang/{idgudang}/transfer-barang', [App\Http\Controllers\TransferBarangController::class, 'store'])->name('gudang.transfer-barang.store');

    Route::get('/gudang/{idgudang}/personel', [App\Http\Controllers\PersonelController::class, 'index'])->name('gudang.personel');
    Route::post('/gudang/{idgudang}/personel', [App\Http\Controllers\PersonelController::class, 'store'])->name('gudang.personel.store');
    Route::put('/gudang/{idgudang}/personel/{id}', [App\Http\Controllers\PersonelController::class, 'update'])->name('gudang.personel.update');
    Route::delete('/gudang/{idgudang}/personel/{id}', [App\Http\Controllers\PersonelController::class, 'destroy'])->name('gudang.personel.destroy');

    Route::get('/gudang/{idgudang}/permintaan/buat-tabel', [App\Http\Controllers\PermintaanPpeController::class, 'create'])->name('gudang.permintaan-ppe.create');
    Route::post('/gudang/{idgudang}/permintaan/buat-tabel/export', [App\Http\Controllers\PermintaanPpeController::class, 'export'])->name('gudang.permintaan-ppe.export');

    Route::get('/gudang/{idgudang}/permintaan', [App\Http\Controllers\PermintaanController::class, 'index'])->name('gudang.permintaan');
    Route::post('/gudang/{idgudang}/permintaan', [App\Http\Controllers\PermintaanController::class, 'store'])->name('gudang.permintaan.store');
    Route::get('/gudang/{idgudang}/permintaan/{id}/pdf', [App\Http\Controllers\PermintaanController::class, 'downloadPdf'])->name('gudang.permintaan.pdf');
    Route::get('/gudang/{idgudang}/permintaan/{id}', [App\Http\Controllers\PermintaanController::class, 'show'])->name('gudang.permintaan.show');
    Route::put('/gudang/{idgudang}/permintaan/{id}', [App\Http\Controllers\PermintaanController::class, 'update'])->name('gudang.permintaan.update');
    Route::delete('/gudang/{idgudang}/permintaan/{id}', [App\Http\Controllers\PermintaanController::class, 'destroy'])->name('gudang.permintaan.destroy');
    Route::post('/gudang/{idgudang}/permintaan/{permintaanId}/item/{itemId}/kedatangan', [App\Http\Controllers\PermintaanController::class, 'storeKedatangan'])->name('gudang.permintaan.kedatangan');

    Route::get('/gudang/{idgudang}/ppe-masuk', [App\Http\Controllers\PpeMasukController::class, 'index'])->name('gudang.ppe-masuk');
    Route::get('/gudang/{idgudang}/ppe-keluar', [App\Http\Controllers\PpeKeluarController::class, 'index'])->name('gudang.ppe-keluar');

    // Data Pemakaian PPE
    Route::get('/gudang/{idgudang}/pemakaian-ppe', [App\Http\Controllers\PemakaianPpeController::class, 'index'])->name('gudang.pemakaian-ppe');
    Route::get('/gudang/{idgudang}/pemakaian-ppe/{personelId}', [App\Http\Controllers\PemakaianPpeController::class, 'show'])->name('gudang.pemakaian-ppe.show');

    // Mobilisasi
    Route::get('/gudang/{idgudang}/mobilisasi', [App\Http\Controllers\MobilisasiController::class, 'index'])->name('gudang.mobilisasi');
    Route::get('/gudang/{idgudang}/mobilisasi/create', [App\Http\Controllers\MobilisasiController::class, 'create'])->name('gudang.mobilisasi.create');
    Route::post('/gudang/{idgudang}/mobilisasi', [App\Http\Controllers\MobilisasiController::class, 'store'])->name('gudang.mobilisasi.store');
    Route::get('/gudang/{idgudang}/mobilisasi/{id}', [App\Http\Controllers\MobilisasiController::class, 'show'])->name('gudang.mobilisasi.show');
    Route::delete('/gudang/{idgudang}/mobilisasi/{id}', [App\Http\Controllers\MobilisasiController::class, 'destroy'])->name('gudang.mobilisasi.destroy');

    Route::get('/gudang/{idgudang}/mobilisasi/{id}/perlengkapan', [App\Http\Controllers\MobilisasiController::class, 'perlengkapan'])->name('gudang.mobilisasi.perlengkapan');
    Route::post('/gudang/{idgudang}/mobilisasi/{id}/perlengkapan', [App\Http\Controllers\MobilisasiController::class, 'storePerlengkapan'])->name('gudang.mobilisasi.perlengkapan.store');
    Route::put('/gudang/{idgudang}/mobilisasi/{id}/perlengkapan/{itemId}', [App\Http\Controllers\MobilisasiController::class, 'updatePerlengkapan'])->name('gudang.mobilisasi.perlengkapan.update');
    Route::delete('/gudang/{idgudang}/mobilisasi/{id}/perlengkapan/{itemId}', [App\Http\Controllers\MobilisasiController::class, 'destroyPerlengkapan'])->name('gudang.mobilisasi.perlengkapan.destroy');

    // Spare Barang (terikat mobilisasi — dikelola dari Data Perlengkapan)
    Route::post('/gudang/{idgudang}/mobilisasi/{id}/spare-barang', [App\Http\Controllers\SpareBarangController::class, 'store'])->name('gudang.mobilisasi.spare.store');
    Route::post('/gudang/{idgudang}/mobilisasi/{id}/spare-barang/{srId}/pakai', [App\Http\Controllers\SpareBarangController::class, 'pakai'])->name('gudang.mobilisasi.spare.pakai');
    Route::post('/gudang/{idgudang}/mobilisasi/{id}/spare-barang/{srId}/kembalikan', [App\Http\Controllers\SpareBarangController::class, 'kembalikan'])->name('gudang.mobilisasi.spare.kembalikan');

    Route::get('/gudang/{idgudang}/mobilisasi/{id}/pengecekan/{personelId}', [App\Http\Controllers\MobilisasiController::class, 'pengecekan'])->name('gudang.mobilisasi.pengecekan');
    Route::put('/gudang/{idgudang}/mobilisasi/{id}/pengecekan/{personelId}', [App\Http\Controllers\MobilisasiController::class, 'updatePengecekan'])->name('gudang.mobilisasi.pengecekan.update');
    Route::post('/gudang/{idgudang}/mobilisasi/{id}/pengecekan/{personelId}/submit', [App\Http\Controllers\MobilisasiController::class, 'submitPersonel'])->name('gudang.mobilisasi.pengecekan.submit');
    Route::post('/gudang/{idgudang}/mobilisasi/{id}/jalankan', [App\Http\Controllers\MobilisasiController::class, 'jalankanProjek'])->name('gudang.mobilisasi.jalankan');

    // Demobilisasi
    Route::get('/gudang/{idgudang}/demobilisasi', [App\Http\Controllers\DemobilisasiController::class, 'index'])->name('gudang.demobilisasi');
    Route::post('/gudang/{idgudang}/demobilisasi/{id}/selesaikan/{personelId}', [App\Http\Controllers\DemobilisasiController::class, 'selesaikan'])->name('gudang.demobilisasi.selesaikan');
    Route::get('/gudang/{idgudang}/demobilisasi/{id}/dokumen-mobilisasi/{personelId}', [App\Http\Controllers\DemobilisasiController::class, 'dokumenMobilisasi'])->name('gudang.demobilisasi.dokumen-mob');
    Route::get('/gudang/{idgudang}/demobilisasi/{id}/dokumen-demobilisasi/{personelId}', [App\Http\Controllers\DemobilisasiController::class, 'dokumenDemobilisasi'])->name('gudang.demobilisasi.dokumen-demob');
    Route::get('/gudang/{idgudang}/demobilisasi/{id}/cek-kelengkapan/{personelId}', [App\Http\Controllers\DemobilisasiController::class, 'cekKelengkapan'])->name('gudang.demobilisasi.cek');
    Route::post('/gudang/{idgudang}/demobilisasi/{id}/cek-kelengkapan/{personelId}', [App\Http\Controllers\DemobilisasiController::class, 'storeCekKelengkapan'])->name('gudang.demobilisasi.cek.store');

    // Approval Demob
    Route::get('/gudang/{idgudang}/approval-demob', [App\Http\Controllers\ApprovalDemobController::class, 'index'])->name('gudang.approval-demob');
    Route::post('/gudang/{idgudang}/approval-demob/{personelId}/approve', [App\Http\Controllers\ApprovalDemobController::class, 'approve'])->name('gudang.approval-demob.approve');
    Route::post('/gudang/{idgudang}/approval-demob/{personelId}/reject', [App\Http\Controllers\ApprovalDemobController::class, 'reject'])->name('gudang.approval-demob.reject');
    Route::post('/gudang/{idgudang}/approval-demob/spare/{pemakaianId}/approve', [App\Http\Controllers\ApprovalDemobController::class, 'approveSpare'])->name('gudang.approval-demob.spare.approve');
    Route::post('/gudang/{idgudang}/approval-demob/spare/{pemakaianId}/reject', [App\Http\Controllers\ApprovalDemobController::class, 'rejectSpare'])->name('gudang.approval-demob.spare.reject');

    // Peminjaman PPE
    Route::get('/gudang/{idgudang}/peminjaman-ppe', [App\Http\Controllers\PeminjamanPpeController::class, 'index'])->name('gudang.peminjaman-ppe');
    Route::post('/gudang/{idgudang}/peminjaman-ppe', [App\Http\Controllers\PeminjamanPpeController::class, 'store'])->name('gudang.peminjaman-ppe.store');
    Route::post('/gudang/{idgudang}/peminjaman-ppe/{id}/approve', [App\Http\Controllers\PeminjamanPpeController::class, 'approve'])->name('gudang.peminjaman-ppe.approve');
    Route::post('/gudang/{idgudang}/peminjaman-ppe/{id}/tolak', [App\Http\Controllers\PeminjamanPpeController::class, 'reject'])->name('gudang.peminjaman-ppe.reject');
    Route::post('/gudang/{idgudang}/peminjaman-ppe/{id}/kembalikan', [App\Http\Controllers\PeminjamanPpeController::class, 'kembalikan'])->name('gudang.peminjaman-ppe.kembalikan');
});

Route::get('/dashboard', function () {
    return view('dashboard.index');
});