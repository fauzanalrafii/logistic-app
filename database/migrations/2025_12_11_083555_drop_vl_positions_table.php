<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus dulu FK yg mungkin masih ada (AMANKAN)
        Schema::table('vl_positions', function (Blueprint $table) {
            try {
                $table->dropForeign(['vl_users_id']);
            } catch (\Exception $e) {}
        });

        // Drop tabelnya
        Schema::dropIfExists('vl_positions');
    }

    public function down(): void
    {
        Schema::create('vl_positions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vl_users_id')->nullable();
            $table->string('position_name')->nullable();
            $table->timestamps();
        });
    }
};
