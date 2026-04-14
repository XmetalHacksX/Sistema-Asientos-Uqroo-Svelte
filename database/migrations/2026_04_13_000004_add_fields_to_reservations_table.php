<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->json('guest_info')->nullable()->after('user_id');
            $table->foreignId('reserved_by_user_id')
                ->nullable()
                ->after('guest_info')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('expires_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['reserved_by_user_id']);
            $table->dropColumn(['guest_info', 'reserved_by_user_id', 'expires_at']);
        });
    }
};

