<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_approval_instances', function (Blueprint $table) {
            $table->id();                         // bigint primary key
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke approval_flows
            $table->unsignedBigInteger('approval_flow_id');
            $table->foreign('approval_flow_id')->references('id')->on('vl_approval_flows')->onDelete('restrict')->onUpdate('cascade');

            // Relasi ke projects
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('set null')->onUpdate('cascade');
            $table->string('related_type', 100); // optional untuk polymorphic relation
            $table->unsignedBigInteger('related_id'); // optional

            $table->string('status', 50)->default('PENDING');              // contoh: pending, approved, rejected
            
            // started_at & completed_at TIMESTAMP NULL
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // created_at & updated_at TIMESTAMP NULL
            $table->timestamps();

            // Indexes
            $table->index('approval_flow_id', 'approval_instances_flow_id_index');
            $table->index('project_id', 'approval_instances_project_id_index');
            $table->index(['related_type', 'related_id'], 'approval_instances_related_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_approval_instances');
    }
};
