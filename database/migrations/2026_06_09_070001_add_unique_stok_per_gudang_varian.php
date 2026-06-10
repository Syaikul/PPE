<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gabungkan duplikat yang sudah ada: qty dijumlahkan, sisanya dihapus.
        $groups = DB::table('stok')
            ->select('idgudang', 'idbarangvarian', DB::raw('MIN(id) as keep_id'), DB::raw('SUM(qty) as total_qty'))
            ->groupBy('idgudang', 'idbarangvarian')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $g) {
            DB::table('stok')->where('id', $g->keep_id)->update(['qty' => $g->total_qty]);
            DB::table('stok')
                ->where('idgudang', $g->idgudang)
                ->where('idbarangvarian', $g->idbarangvarian)
                ->where('id', '!=', $g->keep_id)
                ->delete();
        }

        Schema::table('stok', function (Blueprint $table) {
            $table->unique(['idgudang', 'idbarangvarian'], 'stok_gudang_varian_unique');
        });
    }

    public function down(): void
    {
        Schema::table('stok', function (Blueprint $table) {
            $table->dropUnique('stok_gudang_varian_unique');
        });
    }
};
