<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_boq_headers', function (Blueprint $table) {
            // Tambahkan kolom status_id baru
            $table->unsignedBigInteger('status_id')->nullable()->after('status');

            $table->foreign('status_id')->references('id')->on('vl_status')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });

        // Migrasi nilai status lama -> status_id baru
        DB::table('vl_boq_headers')->get()->each(function ($row) {
            $status = DB::table('vl_status')
                ->where('type', 'boq')
                ->where('name', $row->status)
                ->first();

            DB::table('vl_boq_headers')
                ->where('id', $row->id)
                ->update(['status_id' => $status->id ?? null]);
        });

        // Setelah data dipindahkan, hapus kolom status lama
        Schema::table('vl_boq_headers', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        // Tambahkan lagi kolom status string
        Schema::table('vl_boq_headers', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('status_id');
        });

        // Migrasi status_id kembali ke string
        DB::table('vl_boq_headers')->get()->each(function ($row) {
            $status = DB::table('vl_status')->find($row->status_id);

            DB::table('vl_boq_headers')
                ->where('id', $row->id)
                ->update(['status' => $status->name ?? null]);
        });

        // Hapus FK & kolom status_id
        Schema::table('vl_boq_headers', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
