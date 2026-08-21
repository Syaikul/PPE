<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('manager')->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->boolean('all_gudang')->default(false)->after('is_active');
            $table->string('google_id')->nullable()->after('all_gudang');
        });

        Schema::create('user_gudang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('idgudang');
            $table->timestamps();

            $table->unique(['user_id', 'idgudang']);
        });

        DB::table('users')->update([
            'role'       => 'superadmin',
            'is_active'  => true,
            'all_gudang' => true,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('user_gudang');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'all_gudang', 'google_id']);
        });
    }
};
