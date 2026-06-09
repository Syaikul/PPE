<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/gudang/{idgudang}/stok', [App\Http\Controllers\StokController::class, 'index'])->name('gudang.stok');
    Route::post('/gudang/{idgudang}/stok', [App\Http\Controllers\StokController::class, 'store'])->name('gudang.stok.store');
    Route::put('/gudang/{idgudang}/stok/{id}', [App\Http\Controllers\StokController::class, 'update'])->name('gudang.stok.update');
    Route::delete('/gudang/{idgudang}/stok/{id}', [App\Http\Controllers\StokController::class, 'destroy'])->name('gudang.stok.destroy');

    Route::get('/gudang/{idgudang}/personel', [App\Http\Controllers\PersonelController::class, 'index'])->name('gudang.personel');
    Route::post('/gudang/{idgudang}/personel', [App\Http\Controllers\PersonelController::class, 'store'])->name('gudang.personel.store');
    Route::put('/gudang/{idgudang}/personel/{id}', [App\Http\Controllers\PersonelController::class, 'update'])->name('gudang.personel.update');
    Route::delete('/gudang/{idgudang}/personel/{id}', [App\Http\Controllers\PersonelController::class, 'destroy'])->name('gudang.personel.destroy');

    Route::get('/gudang/{idgudang}/permintaan', [App\Http\Controllers\PermintaanController::class, 'index'])->name('gudang.permintaan');
    Route::post('/gudang/{idgudang}/permintaan', [App\Http\Controllers\PermintaanController::class, 'store'])->name('gudang.permintaan.store');
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
});

Route::get('/dashboard', function () {
    return view('dashboard.index');
});