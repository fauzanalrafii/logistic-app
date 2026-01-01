<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_partners', function (Blueprint $table) {
            $table->id();                     // bigint primary key
            $table->char('uuid', 36)->unique(); // UUID publik
            $table->string('name', 255);           // Nama partner
            $table->string('code', 100);           // Kode partner
            $table->string('type', 50);           // Tipe partner, misal: supplier/client
            $table->string('status', 50)->default('ACTIVE');
            $table->timestamps();

            $table->unique('code', 'partners_code_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_partners');
    }
};
