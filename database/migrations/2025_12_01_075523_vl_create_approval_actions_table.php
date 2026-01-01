<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_approval_actions', function (Blueprint $table) {
            $table->id();                         // BIGINT UNSIGNED PRIMARY KEY
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke approval_instances
            $table->unsignedBigInteger('approval_instance_id');
            $table->foreign('approval_instance_id')->references('id')->on('vl_approval_instances')->onDelete('cascade')->onUpdate('cascade');

            // Relasi ke approval_steps
            $table->unsignedBigInteger('approval_step_id');
            $table->foreign('approval_step_id')->references('id')->on('vl_approval_steps')->onDelete('cascade')->onUpdate('cascade');

            // Relasi ke users
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('vl_users')->onDelete('restrict')->onUpdate('cascade');

            // Aksi approval: APPROVE atau REJECT
            $table->enum('action', ['APPROVE','REJECT']);

            $table->text('comment')->nullable();

            // Timestamp aksi
            $table->timestamp('acted_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            // Indexes
            $table->index('approval_instance_id', 'approval_actions_instance_id_index');
            $table->index('approval_step_id', 'approval_actions_step_id_index');
            $table->index('user_id', 'approval_actions_user_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_approval_actions');
    }
};
