<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_assets', function (Blueprint $table) {
            // Tambahkan kolom status_id baru
            $table->unsignedBigInteger('status_id')->nullable()->after('status');

            $table->foreign('status_id')->references('id')->on('vl_status')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });

        // Migrasi nilai status lama -> status_id baru
        DB::table('vl_assets')->get()->each(function ($row) {
            $status = DB::table('vl_status')
                ->where('type', 'asset')
                ->where('name', $row->status)
                ->first();

            DB::table('vl_assets')
                ->where('id', $row->id)
                ->update(['status_id' => $status->id ?? null]);
        });

        // Setelah data dipindahkan, hapus kolom status lama
        Schema::table('vl_assets', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        // Tambah kembali kolom status string
        Schema::table('vl_assets', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('status_id');
        });

        // Migrasi status_id -> string kembali
        DB::table('vl_assets')->get()->each(function ($row) {
            $status = DB::table('vl_status')->find($row->status_id);
            DB::table('vl_assets')
                ->where('id', $row->id)
                ->update(['status' => $status->name ?? null]);
        });

        // Hapus kolom status_id & FK
        Schema::table('vl_assets', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
