<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vl_projects', function (Blueprint $table) {
            $table->id(); // bigint id (primary key)
            $table->char('uuid', 36)->unique(); // UUID

            $table->string('code', 100)->nullable();
            $table->enum('source', ['OSS', 'MANUAL'])->default('MANUAL');
            $table->string('oss_reference_id', 100)->nullable();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('location', 500)->nullable();
            $table->string('area', 255)->nullable();
            $table->string('project_type', 255)->nullable();
            $table->string('status', 50)->nullable();

            // relasi ke users.id (planner)
            $table->unsignedBigInteger('planner_id')->nullable();
            $table->foreign('planner_id')->references('id')->on('vl_users')->onDelete('set null')->onUpdate('cascade');

            $table->date('target_completion_date')->nullable();
            $table->timestamps();

            $table->index('source');
            $table->index('status');
            $table->index('planner_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vl_projects');
    }
};
