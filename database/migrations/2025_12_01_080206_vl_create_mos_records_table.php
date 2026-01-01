<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_mos_records', function (Blueprint $table) {
            $table->id();                         // BIGINT PRIMARY KEY
            $table->char('uuid', 36)->unique();   // UUID publik

            // Relasi ke work_orders
            $table->unsignedBigInteger('work_order_id');
            $table->foreign('work_order_id')->references('id')->on('vl_work_orders')->onDelete('cascade')->onUpdate('cascade');

            $table->string('material_code', 100)->nullable();
            $table->string('material_description', 255)->nullable();
            $table->decimal('qty_spk', 18, 4)->nullable();
            $table->decimal('qty_arrived', 18, 4)->nullable();
            $table->date('arrival_date')->nullable();
            $table->string('remarks', 255)->nullable();

            // Relasi ke documents sebagai bukti
            $table->unsignedBigInteger('evidence_document_id')->nullable();
            $table->foreign('evidence_document_id')->references('id')->on('vl_documents')->onDelete('set null')->onUpdate('cascade');

            // Index
            $table->index('work_order_id', 'mos_records_work_order_id_index');
            $table->index('evidence_document_id', 'mos_records_document_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_mos_records');
    }
};
