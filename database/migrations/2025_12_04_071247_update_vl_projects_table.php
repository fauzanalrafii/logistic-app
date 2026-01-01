<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_projects', function (Blueprint $table) {
            // Hapus FK planner_id
            $table->dropForeign(['planner_id']);
            // planner_id tetap ada tapi tidak lagi FK
        });
    }

    public function down(): void
    {
        Schema::table('vl_projects', function (Blueprint $table) {
            // Buat FK lagi kalau rollback
            $table->foreign('planner_id')->references('id')->on('vl_users')
                  ->onDelete('set null')->onUpdate('cascade');
        });
    }
};
