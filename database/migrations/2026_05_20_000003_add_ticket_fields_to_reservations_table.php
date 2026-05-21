<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('ticket_token', 64)->nullable()->unique()->after('expires_at');
            $table->timestamp('validated_at')->nullable()->after('ticket_token');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['ticket_token', 'validated_at']);
        });
    }
};
