<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom status_id
        Schema::table('vl_partners', function (Blueprint $table) {
            $table->unsignedBigInteger('status_id')->nullable()->after('status');
            $table->foreign('status_id')
                ->references('id')->on('vl_status')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });

        // Mapping status string → status_id
        DB::table('vl_partners')->get()->each(function ($row) {
            $status = DB::table('vl_status')
                ->where('type', 'partner')
                ->where('name', $row->status)
                ->first();

            DB::table('vl_partners')
                ->where('id', $row->id)
                ->update(['status_id' => $status->id ?? null]);
        });

        // Hapus kolom lama status string
        Schema::table('vl_partners', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        // Tambahkan kembali kolom status string
        Schema::table('vl_partners', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('status_id');
        });

        // Migrasi balik status_id → status string
        DB::table('vl_partners')->get()->each(function ($row) {
            $status = DB::table('vl_status')->find($row->status_id);

            DB::table('vl_partners')
                ->where('id', $row->id)
                ->update(['status' => $status->name ?? null]);
        });

        // Hapus FK & kolom status_id
        Schema::table('vl_partners', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
