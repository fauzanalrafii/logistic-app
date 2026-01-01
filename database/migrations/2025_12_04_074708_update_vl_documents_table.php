<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_documents', function (Blueprint $table) {
            // Hapus FK ke vl_users
            // Gunakan array nama kolom
            $table->dropForeign(['uploaded_by']);

            // Jika mau, kolom uploaded_by tetap ada tanpa FK
            // $table->unsignedBigInteger('uploaded_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vl_documents', function (Blueprint $table) {
            // Rollback: buat FK lagi
            $table->foreign('uploaded_by')
                  ->references('id')->on('vl_users')
                  ->onDelete('set null')->onUpdate('cascade');
        });
    }
};
