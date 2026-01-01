<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_construction_tasks', function (Blueprint $table) {
            $table->id();                         // bigint primary key
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke projects
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('cascade')->onUpdate('cascade');

            // Relasi ke work_orders
            $table->unsignedBigInteger('work_order_id')->nullable();
            $table->foreign('work_order_id')->references('id')->on('vl_work_orders')->onDelete('set null')->onUpdate('cascade');
            
            // Relasi ke partners sebagai penugasan
            $table->unsignedBigInteger('assigned_to_partner_id')->nullable();
            $table->foreign('assigned_to_partner_id')->references('id')->on('vl_partners')->onDelete('set null')->onUpdate('cascade');

            // last_update_by BIGINT UNSIGNED NULL
            $table->unsignedBigInteger('last_update_by')->nullable();
            $table->foreign('last_update_by')->references('id')->on('vl_users')->onDelete('set null')->onUpdate('cascade');

            $table->string('name', 255);                // Nama task
            $table->string('location_detail', 500)->nullable(); // Detail lokasi
            $table->decimal('progress_percent', 5, 2)->default(0.00); // Progress %
            $table->string('status', 50)->default('NOT_STARTED');      // Status task

            $table->date('start_date')->nullable();
            $table->date('end_date_estimate')->nullable();
            $table->date('end_date_actual')->nullable();
            $table->timestamp('last_update_at')->nullable();

            // created_at & updated_at
            $table->timestamps();

            // Indexes
            $table->index('project_id', 'construction_tasks_project_id_index');
            $table->index('work_order_id', 'construction_tasks_work_order_id_index');
            $table->index('assigned_to_partner_id', 'construction_tasks_partner_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_construction_tasks');
    }
};
