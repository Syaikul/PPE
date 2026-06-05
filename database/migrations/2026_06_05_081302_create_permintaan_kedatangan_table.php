<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_kedatangan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permintaan_item_id')->constrained('permintaan_item')->cascadeOnDelete();
            $table->date('tanggal');
            $table->integer('qty_datang');
            $table->string('no_po')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permintaan_kedatangan');
    }
};
