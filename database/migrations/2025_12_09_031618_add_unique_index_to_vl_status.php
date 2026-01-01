<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // sebelum tambahkan unique, pastikan kolom tidak ada spasi
        DB::statement("UPDATE vl_status SET name = TRIM(name), type = TRIM(type)");

        Schema::table('vl_status', function (Blueprint $table) {
            $table->unique(['name', 'type'], 'vl_status_name_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('vl_status', function (Blueprint $table) {
            $table->dropUnique('vl_status_name_type_unique');
        });
    }
};
