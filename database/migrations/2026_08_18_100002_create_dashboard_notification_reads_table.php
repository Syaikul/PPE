<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_notification_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_key', 191);
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['user_id', 'event_key'], 'dashboard_notification_user_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_notification_reads');
    }
};
