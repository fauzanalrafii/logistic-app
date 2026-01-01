<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vl_boq_items', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED PRIMARY KEY
            $table->char('uuid', 36)->unique(); // UUID publik

            // Relasi ke boq_headers
            $table->unsignedBigInteger('boq_header_id');
            $table->foreign('boq_header_id')->references('id')->on('vl_boq_headers')->onDelete('cascade')->onUpdate('cascade');

            $table->string('material_code', 100)->nullable();      // kode material
            $table->string('material_description', 255); // deskripsi material
            $table->string('uom', 50)->nullable(); // unit of measure
            $table->decimal('qty', 18, 4)->default(0);        // kuantitas
            $table->decimal('unit_price_estimate', 18, 2)->nullable(); // harga perkiraan
            $table->string('remarks', 255)->nullable();

            // Index
            $table->index('boq_header_id', 'boq_items_header_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vl_boq_items');
    }
};
