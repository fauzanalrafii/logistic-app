<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_construction_tasks', function (Blueprint $table) {
            // Tambah kolom status_id
            $table->unsignedBigInteger('status_id')->nullable()->after('status');

            // Foreign key
            $table->foreign('status_id')->references('id')->on('vl_status')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });

        // Migrasi status lama -> status_id
        DB::table('vl_construction_tasks')->get()->each(function ($row) {
            $status = DB::table('vl_status')
                ->where('type', 'construction')
                ->where('name', $row->status)
                ->first();

            DB::table('vl_construction_tasks')
                ->where('id', $row->id)
                ->update(['status_id' => $status->id ?? null]);
        });

        // Hapus kolom status lama
        Schema::table('vl_construction_tasks', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        // Tambahkan kembali kolom lama (STRING)
        Schema::table('vl_construction_tasks', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('status_id');
        });

        // Migrasikan status_id kembali ke nama
        DB::table('vl_construction_tasks')->get()->each(function ($row) {
            $status = DB::table('vl_status')->find($row->status_id);
            DB::table('vl_construction_tasks')
                ->where('id', $row->id)
                ->update(['status' => $status->name ?? null]);
        });

        // Hapus kolom baru & FK
        Schema::table('vl_construction_tasks', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
