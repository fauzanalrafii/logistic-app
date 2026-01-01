<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_test_results', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
            $table->char('uuid', 36)->unique();

            // Relasi ke projects
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('cascade')->onUpdate('cascade');

            // Network element
            $table->string('network_element_type', 50);
            $table->unsignedBigInteger('network_element_id')->nullable();

            // Test type
            $table->string('test_type', 50);

            // Status VARCHAR(50) NOT NULL DEFAULT 'PENDING'
            $table->string('status', 50)->default('PENDING');

            // Measured values JSON NULL
            $table->json('measured_values')->nullable();

            // test_date DATE NULL
            $table->date('test_date')->nullable();

            // tested_by BIGINT NULL
            $table->unsignedBigInteger('tested_by')->nullable();
            $table->foreign('tested_by')->references('id')->on('vl_users')->onDelete('set null')->onUpdate('cascade');

            // document_id BIGINT NULL
            $table->unsignedBigInteger('document_id')->nullable();
            $table->foreign('document_id')->references('id')->on('vl_documents')->onDelete('set null')->onUpdate('cascade');

            // created_at & updated_at
            $table->timestamps();

            // Indexes
            $table->index('project_id', 'test_results_project_id_index');
            $table->index('test_type', 'test_results_test_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_test_results');
    }
};
