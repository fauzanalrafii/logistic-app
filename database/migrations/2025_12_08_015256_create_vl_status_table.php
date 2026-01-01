<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_status', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->integer('no')->nullable(); // Nomor urut status
            $table->string('name', 100); // Nama status
            $table->string('type', 50)->nullable(); // Tipe status: project, survey, dokumen, construction, approval
            $table->text('description')->nullable(); // Penjelasan kegunaan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_status');
    }
};
