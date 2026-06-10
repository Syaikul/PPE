<?php

use App\Services\PersonelStatusService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PersonelStatusService::resyncAll();
    }

    public function down(): void
    {
        // Tidak perlu rollback — status akan disinkronkan ulang saat mobilisasi/demob berjalan.
    }
};
