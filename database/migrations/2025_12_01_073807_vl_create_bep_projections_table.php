<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_bep_projections', function (Blueprint $table) {
            $table->id(); // bigint primary key
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke projects
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('cascade')->onUpdate('cascade');

            $table->integer('version')->default(1);          // versi projection
            $table->decimal('capex', 18, 2)->nullable();      // CAPEX
            $table->decimal('opex_estimate', 18, 2)->nullable();
            $table->decimal('revenue_estimate', 18, 2)->nullable(); // Revenue estimate
            $table->integer('bep_months')->nullable();        // BEP months
            $table->string('status', 50)->default('DRAFT');             // status, misal: draft, approved
       
            // created_by BIGINT UNSIGNED NOT NULL
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('vl_users')->onDelete('restrict')->onUpdate('cascade');

            // submitted_at TIMESTAMP NULL
            $table->timestamp('submitted_at')->nullable();

            // created_at & updated_at TIMESTAMP NULL
            $table->timestamps();

            // Indexes
            $table->index('project_id', 'bep_projections_project_id_index');
            $table->index('created_by', 'bep_projections_created_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_bep_projections');
    }
};
