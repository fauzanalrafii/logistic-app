<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_assets', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
            $table->char('uuid', 36)->unique();

            // Relasi ke projects
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('cascade')->onUpdate('cascade');

            $table->string('asset_type', 50);
            $table->string('code', 100)->unique();

            // Nullable fields
            $table->string('name', 255)->nullable();
            $table->string('location', 500)->nullable();
            $table->string('capacity', 255)->nullable();
            $table->string('inventory_reference_id', 100)->nullable();

            $table->string('status', 50)->default('PLANNED');

            // created_at & updated_at
            $table->timestamps();

            // Index
            $table->index('project_id', 'assets_project_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_assets');
    }
};
