<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Demob: dukung kondisi parsial (mis. 1 dari 2 unit rusak, sisanya layak).
        Schema::table('demob_pengecekan', function (Blueprint $table) {
            $table->unsignedInteger('jumlah')->nullable()->after('idsubbarang');
            $table->unsignedInteger('qty_bermasalah')->nullable()->after('kondisi');
        });

        // 2. By Request per personel MOB, atau untuk "User" (klien).
        Schema::table('mobilisasi_perlengkapan', function (Blueprint $table) {
            $table->unsignedInteger('idposisi')->nullable()->change();
            $table->foreignId('mobilisasi_personel_id')->nullable()->after('idposisi')
                ->constrained('mobilisasi_personel')->cascadeOnDelete();
            $table->boolean('untuk_user')->default(false)->after('jenis');
        });

        // 3. Spare barang terikat ke mobilisasi.
        Schema::table('spare_barang', function (Blueprint $table) {
            $table->foreignId('mobilisasi_id')->nullable()->after('idgudang')
                ->constrained('mobilisasi')->nullOnDelete();
        });

        // 4. Rename status personel: Onshore -> Onsite, Offshore -> Offsite.
        DB::table('personel')->where('status', 'Onshore')->update(['status' => 'Onsite']);
        DB::table('personel')->where('status', 'Offshore')->update(['status' => 'Offsite']);
        Schema::table('personel', function (Blueprint $table) {
            $table->string('status')->default('Offsite')->change();
        });
    }

    public function down(): void
    {
        DB::table('personel')->where('status', 'Onsite')->update(['status' => 'Onshore']);
        DB::table('personel')->where('status', 'Offsite')->update(['status' => 'Offshore']);
        Schema::table('personel', function (Blueprint $table) {
            $table->string('status')->default('Offshore')->change();
        });

        Schema::table('spare_barang', function (Blueprint $table) {
            $table->dropConstrainedForeignId('mobilisasi_id');
        });

        Schema::table('mobilisasi_perlengkapan', function (Blueprint $table) {
            $table->dropColumn('untuk_user');
            $table->dropConstrainedForeignId('mobilisasi_personel_id');
        });

        Schema::table('demob_pengecekan', function (Blueprint $table) {
            $table->dropColumn(['jumlah', 'qty_bermasalah']);
        });
    }
};
