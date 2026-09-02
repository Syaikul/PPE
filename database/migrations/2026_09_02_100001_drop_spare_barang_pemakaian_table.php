<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('spare_barang_pemakaian');
    }

    public function down(): void
    {
        Schema::create('spare_barang_pemakaian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spare_barang_item_id')->constrained('spare_barang_item')->cascadeOnDelete();
            $table->foreignId('personel_id')->constrained('personel')->cascadeOnDelete();
            $table->integer('qty')->default(1);
            $table->string('status', 20)->default('menunggu');
            $table->text('catatan')->nullable();
            $table->text('approval_catatan')->nullable();
            $table->date('tanggal');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }
};
