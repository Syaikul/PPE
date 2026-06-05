<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personel_posisi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personel_id')->constrained('personel')->cascadeOnDelete();
            $table->unsignedInteger('idposisi');
            $table->timestamps();

            $table->unique(['personel_id', 'idposisi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personel_posisi');
    }
};
