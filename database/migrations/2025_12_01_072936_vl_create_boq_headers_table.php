<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_boq_headers', function (Blueprint $table) {
            $table->id();                         // bigint primary key
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke projects
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('cascade')->onUpdate('cascade');

            $table->enum('type', ['ON_DESK', 'ON_SITE']);
            $table->integer('version')->default(1);           // versi BOQ
            $table->string('status', 50)->default('DRAFT');    // status, misal: draft, approved
        
            // total_cost_estimate DECIMAL(18,2) NULL
            $table->decimal('total_cost_estimate', 18, 2)->nullable();

            // created_by BIGINT UNSIGNED NOT NULL
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('vl_users')->onDelete('restrict')->onUpdate('cascade');

            // submitted_at TIMESTAMP NULL
            $table->timestamp('submitted_at')->nullable();

            // created_at & updated_at TIMESTAMP NULL
            $table->timestamps();

            // Indexes
            $table->index('project_id', 'boq_headers_project_id_index');
            $table->index('type', 'boq_headers_type_index');
            $table->index('created_by', 'boq_headers_created_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_boq_headers');
    }
};
