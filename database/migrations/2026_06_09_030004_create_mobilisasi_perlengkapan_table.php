<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobilisasi_perlengkapan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobilisasi_id')->constrained('mobilisasi')->cascadeOnDelete();
            $table->unsignedInteger('idposisi');
            $table->unsignedInteger('idsubbarang');
            $table->integer('qty')->default(1);
            $table->string('jenis')->default('perlengkapan'); // perlengkapan | by_request
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobilisasi_perlengkapan');
    }
};
