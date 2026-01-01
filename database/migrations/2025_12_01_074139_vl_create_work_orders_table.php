<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_work_orders', function (Blueprint $table) {
            $table->id();                         // bigint primary key
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke projects
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('cascade')->onUpdate('cascade');

            $table->enum('type', ['SURVEY','IMPLEMENTATION','MANDOR']); // tipe work order, misal: installation, maintenance
            $table->string('number', 100)->unique(); // nomor work order

            // Relasi ke partners
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->foreign('partner_id')->references('id')->on('vl_partners')->onDelete('set null')->onUpdate('cascade')->nullable();

            // status VARCHAR(50) NOT NULL DEFAULT 'DRAFT'
            $table->string('status', 50)->default('DRAFT');

            // issue_date & due_date DATE NULL
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();

            // generated_from VARCHAR(50) NULL
            $table->string('generated_from', 50)->nullable();

            // created_by BIGINT UNSIGNED NOT NULL
            $table->unsignedBigInteger('created_by');
            $table->foreign('created_by')->references('id')->on('vl_users')->onDelete('restrict')->onUpdate('cascade');

            // created_at & updated_at TIMESTAMP NULL
            $table->timestamps();

            // Indexes
            $table->index('project_id', 'work_orders_project_id_index');
            $table->index('partner_id', 'work_orders_partner_id_index');
            $table->index('type', 'work_orders_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_work_orders');
    }
};
