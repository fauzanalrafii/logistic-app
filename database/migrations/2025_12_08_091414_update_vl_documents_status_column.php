<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_documents', function (Blueprint $table) {

            // 1️⃣ Tambahkan kolom status_id baru (nullable dulu)
            $table->unsignedBigInteger('status_id')->nullable()->after('status');

            // 2️⃣ Buat foreign key ke vl_status
            $table->foreign('status_id')
                ->references('id')
                ->on('vl_status')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });

        // 3️⃣ Mapping status STRING lama → vl_status.id (sementara)
        DB::statement("
            UPDATE vl_documents d
            JOIN vl_status s ON d.status = s.name AND s.type = 'document'
            SET d.status_id = s.id
        ");
    }

    public function down(): void
    {
        Schema::table('vl_documents', function (Blueprint $table) {

            // Hapus kolom status_id & foreign key
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
    }
};
