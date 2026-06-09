<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobilisasi', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('idgudang');
            $table->string('sr')->nullable();
            $table->string('lokasi_pekerjaan')->nullable();
            $table->string('status')->default('draft'); // draft, berjalan, selesai
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobilisasi');
    }
};
