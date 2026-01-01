<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_survey_results', function (Blueprint $table) {
            $table->id();                         // BIGINT PRIMARY KEY
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke projects
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('cascade')->onUpdate('cascade');

            // Relasi ke work_orders
            $table->unsignedBigInteger('work_order_id');
            $table->foreign('work_order_id')->references('id')->on('vl_work_orders')->onDelete('cascade')->onUpdate('cascade');

            // Relasi ke boq_headers (opsional)
            $table->unsignedBigInteger('boq_header_id')->nullable();
            $table->foreign('boq_header_id')->references('id')->on('vl_boq_headers')->onDelete('set null')->onUpdate('cascade');

            $table->text('notes')->nullable();
            $table->string('status', 50)->default('DRAFT'); // status survey

            // Relasi ke users
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->foreign('submitted_by')->references('id')->on('vl_users')->onDelete('set null')->onUpdate('cascade');

            $table->timestamp('submitted_at')->nullable();

            $table->unsignedBigInteger('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('vl_users')->onDelete('set null')->onUpdate('cascade');

            $table->timestamp('approved_at')->nullable();

            // Index
            $table->index('project_id', 'survey_results_project_id_index');
            $table->index('work_order_id', 'survey_results_work_order_id_index');
            $table->index('boq_header_id', 'survey_results_boq_header_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_survey_results');
    }
};
