<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_survey_results', function (Blueprint $table) {
            // tambah status_id kolom baru
            $table->unsignedBigInteger('status_id')->nullable()->after('status');

            $table->foreign('status_id')->references('id')->on('vl_status')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });

        // Migrasi data lama (string) --> status_id
        DB::table('vl_survey_results')->get()->each(function ($row) {
            $status = DB::table('vl_status')
                ->where('type', 'survey')
                ->where('name', $row->status)
                ->first();

            DB::table('vl_survey_results')
                ->where('id', $row->id)
                ->update(['status_id' => $status->id ?? null]);
        });

        // hapus kolom status lama
        Schema::table('vl_survey_results', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    public function down(): void
    {
        // restore kolom status string
        Schema::table('vl_survey_results', function (Blueprint $table) {
            $table->string('status', 50)->nullable()->after('status_id');
        });

        // balik status_id → status string
        DB::table('vl_survey_results')->get()->each(function ($row) {
            $status = DB::table('vl_status')->find($row->status_id);

            DB::table('vl_survey_results')
                ->where('id', $row->id)
                ->update(['status' => $status->name ?? null]);
        });

        // hapus FK status_id
        Schema::table('vl_survey_results', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
