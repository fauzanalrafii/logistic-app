<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_projects', function (Blueprint $table) {

            // tambah kolom status_id
            $table->unsignedBigInteger('status_id')->nullable()->after('project_type');

            // isi sementara status_id dengan null, nanti diisi mapping
        });
    }

    public function down(): void
    {
        Schema::table('vl_projects', function (Blueprint $table) {
            $table->dropColumn('status_id');
        });
    }
};
