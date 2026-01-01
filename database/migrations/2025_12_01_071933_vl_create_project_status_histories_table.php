<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_project_status_histories', function (Blueprint $table) {
            $table->id();                         // bigint primary key
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke projects
            $table->unsignedBigInteger('project_id');
            $table->foreign('project_id')->references('id')->on('vl_projects')->onDelete('cascade')->onUpdate('cascade');

            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);

            // Relasi ke users sebagai pengubah status
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->foreign('changed_by')->references('id')->on('vl_users')->onDelete('set null')->onUpdate('cascade');

            $table->timestamp('changed_at')->useCurrent;       // Waktu perubahan status
            $table->text('note')->nullable();

            $table->index('project_id', 'project_status_histories_project_id_index');
            $table->index('changed_by', 'project_status_histories_changed_by_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_project_status_histories');
    }
};
