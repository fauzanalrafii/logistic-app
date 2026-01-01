<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambah kolom status_id
        Schema::table('vl_work_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id')->nullable()->after('partner_id');
        });

        // 2) Migrate data: konversi old VARCHAR status -> ID dari tabel vl_status (jika ada)
        DB::statement("
            UPDATE vl_work_orders wo
            JOIN vl_status s ON s.name = wo.status AND s.type = 'work_order'
            SET wo.status_id = s.id
        ");

        // 3) Drop kolom status lama
        Schema::table('vl_work_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        // 4) Tambahkan foreign key
        Schema::table('vl_work_orders', function (Blueprint $table) {
            $table->foreign('status_id')
                ->references('id')->on('vl_status')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('vl_work_orders', function (Blueprint $table) {
            $table->string('status', 50)->nullable();
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
