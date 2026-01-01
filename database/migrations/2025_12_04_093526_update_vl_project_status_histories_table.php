<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_project_status_histories', function (Blueprint $table) {
            // Drop foreign key lama
            $table->dropForeign(['changed_by']);
        });
    }

    public function down(): void
    {
        Schema::table('vl_project_status_histories', function (Blueprint $table) {
            // Rollback: buat FK lagi ke vl_users
            $table->foreign('changed_by')->references('id')->on('vl_users')
                  ->onDelete('set null')->onUpdate('cascade');
        });
    }
};
