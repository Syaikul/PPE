<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobilisasi_personel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mobilisasi_id')->constrained('mobilisasi')->cascadeOnDelete();
            $table->foreignId('personel_id')->constrained('personel')->cascadeOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['mobilisasi_id', 'personel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobilisasi_personel');
    }
};
