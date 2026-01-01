<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_approval_steps', function (Blueprint $table) {
            $table->id();                         // bigint primary key
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke approval_flows
            $table->unsignedBigInteger('approval_flow_id');
            $table->foreign('approval_flow_id')->references('id')->on('vl_approval_flows')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('step_order');        // urutan step
            $table->string('name', 255);               // nama step

            // Relasi ke roles
            $table->unsignedBigInteger('required_role_id')->nullable();
            $table->foreign('required_role_id')->references('id')->on('vl_roles')->onDelete('set null')->onUpdate('cascade');
            
            $table->timestamps();

            $table->index('approval_flow_id', 'approval_steps_flow_id_index');
            $table->index('required_role_id', 'approval_steps_required_role_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_approval_steps');
    }
};
