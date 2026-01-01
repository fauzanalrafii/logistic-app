<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_approval_flows', function (Blueprint $table) {
            $table->id();                     // bigint primary key
            $table->char('uuid', 36)->unique(); // UUID publik
            $table->string('name', 255);           // Nama approval flow
            $table->string('process_type', 100);   // Tipe proses, misal: project, request, leave
            $table->boolean('is_active')->default(1);
            $table->timestamps();

            $table->index('process_type', 'approval_flows_process_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_approval_flows');
    }
};
