<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom vl_users_id (nullable dulu)
        Schema::table('vl_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('vl_users_id')->nullable()->after('id')->index();
        });

        // 2. Isi vl_users_id menggunakan user_l_ID (jika memang user_l_ID == vl_users.id)
        DB::statement("
            UPDATE vl_positions 
            SET vl_users_id = user_l_ID
        ");

        // 3. Pastikan semua vl_users_id valid
        // Jika ada yang tidak cocok, kamu harus betulkan manual di database

        // 4. Jadikan NOT NULL jika required
        Schema::table('vl_positions', function (Blueprint $table) {
            $table->unsignedBigInteger('vl_users_id')->nullable(false)->change();
        });

        // 5. Hapus kolom lama
        Schema::table('vl_positions', function (Blueprint $table) {
            $table->dropColumn('user_l_ID');
        });

        // 6. Tambah foreign key
        Schema::table('vl_positions', function (Blueprint $table) {
            $table->foreign('vl_users_id')
                ->references('id')
                ->on('vl_users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('vl_positions', function (Blueprint $table) {
            $table->dropForeign(['vl_users_id']);
            $table->unsignedBigInteger('user_l_ID')->nullable();
            $table->dropColumn('vl_users_id');
        });
    }
};
