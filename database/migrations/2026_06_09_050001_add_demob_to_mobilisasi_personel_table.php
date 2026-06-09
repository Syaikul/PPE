<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mobilisasi_personel', function (Blueprint $table) {
            // demob_status: null = OnSite (mob), 'belum_cek', 'menunggu_approval', 'selesai'
            $table->string('demob_status')->nullable()->after('submitted_at');
            $table->date('tanggal_demob')->nullable()->after('demob_status');
            $table->timestamp('demob_checked_at')->nullable()->after('tanggal_demob');
            $table->timestamp('approved_at')->nullable()->after('demob_checked_at');
            $table->text('approval_catatan')->nullable()->after('approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('mobilisasi_personel', function (Blueprint $table) {
            $table->dropColumn(['demob_status', 'tanggal_demob', 'demob_checked_at', 'approved_at', 'approval_catatan']);
        });
    }
};
