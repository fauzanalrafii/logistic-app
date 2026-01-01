<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_test_results', function (Blueprint $table) {
            // Tambahkan kolom baru status_id
            $table->unsignedBigInteger('status_id')->nullable()->after('status');
            $table->foreign('status_id')->references('id')->on('vl_status')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });

        // Migrasi nilai string → status_id
        DB::table('vl_test_results')->get()->each(function ($row) {
            $status = DB::table('vl_status')
                ->where('type', 'test')
                ->where('name', $row->status)
                ->first();

            DB::table('vl_test_results')
                ->where('id', $row->id)
                ->update(['status_id' => $status->id ?? null]);
        });

        // Hapus kolom status lama
        Schema::table('vl_test_results', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        // Tambahkan kembali kolom status string
        Schema::table('vl_test_results', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('status_id');
        });

        // Migrasi balik status_id → status string
        DB::table('vl_test_results')->get()->each(function ($row) {
            $status = DB::table('vl_status')->find($row->status_id);

            DB::table('vl_test_results')
                ->where('id', $row->id)
                ->update(['status' => $status->name ?? null]);
        });

        // Hapus kolom status_id dan foreign key
        Schema::table('vl_test_results', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
