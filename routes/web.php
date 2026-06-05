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
});

Route::get('/dashboard', function () {
    return view('dashboard.index');
});