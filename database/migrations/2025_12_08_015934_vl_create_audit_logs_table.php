<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vl_audit_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action');      // CREATE, UPDATE, DELETE
            $table->string('table_name');  // Nama tabel (vl_projects)
            $table->string('record_id');   // ID data (1, 2, 3...)
            $table->json('old_values')->nullable(); // Data SEBELUM
            $table->json('new_values')->nullable(); // Data SESUDAH
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vl_audit_log');
    }
};
