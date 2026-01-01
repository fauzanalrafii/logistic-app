<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_documents', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
            $table->char('uuid', 36)->unique();

            // Relasi ke projects
            $table->unsignedBigInteger('project_id')->nullable();
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('set null')->onUpdate('cascade');

            // Polymorphic relation
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();

            $table->string('document_type', 50);
            $table->string('file_path', 1000);
            $table->string('file_name', 255);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type', 100)->nullable();

            $table->string('status', 50)->default('DRAFT');

            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->foreign('uploaded_by')->references('id')->on('vl_users')->onDelete('set null')->onUpdate('cascade');

            $table->timestamp('uploaded_at')->nullable();

            // created_at & updated_at
            $table->timestamps();

            // Indexes
            $table->index('project_id', 'documents_project_id_index');
            $table->index(['related_type','related_id'], 'documents_related_index');
            $table->index('uploaded_by', 'documents_uploaded_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_documents');
    }
};
