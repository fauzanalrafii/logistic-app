<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_positions', function (Blueprint $table) {
            $table->id(); // Primary Key

            // Mapping ke user dari database lain (tbl user_l)
            $table->unsignedBigInteger('user_l_ID')->index();

            // Role di aplikasi
            $table->unsignedBigInteger('role_id')->nullable();
            $table->foreign('role_id')->references('id')->on('vl_roles');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_positions');
    }
};
