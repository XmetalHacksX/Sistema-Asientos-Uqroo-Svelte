<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
        });

        Schema::rename('space_nodes', 'nodes');

        Schema::table('nodes', function (Blueprint $table) {
            $table->string('status')->default('active')->after('pos_y');
            $table->boolean('is_occupied')->default(false)->after('status');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('node_id')
                ->references('id')
                ->on('nodes')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['node_id']);
        });

        Schema::table('nodes', function (Blueprint $table) {
            $table->dropColumn(['status', 'is_occupied']);
        });

        Schema::rename('nodes', 'space_nodes');

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreign('node_id')
                ->references('id')
                ->on('space_nodes')
                ->cascadeOnDelete();
        });
    }
};
