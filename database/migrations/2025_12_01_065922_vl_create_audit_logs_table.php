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
        Schema::create('vl_audit_logs', function (Blueprint $table) {
            $table->id(); // bigint id
            $table->char('uuid', 36)->unique(); // UUID

            // Relasi ke users
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('vl_users')->onDelete('set null')->onUpdate('cascade');

            $table->string('action_type', 100);   // contoh: create/update/delete/login
            $table->string('object_type', 100);   // contoh: project/user/approval_flows
            $table->unsignedBigInteger('object_id')->nullable(); // id dari objek yg diubah
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('description', 1000)->nullable();
            $table->timestamp('created_at');
            
            $table->index('user_id');
            $table->index(['object_type', 'object_id'], 'audit_logs_object_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vl_audit_logs');
    }
};
