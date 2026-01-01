<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vl_documents', function (Blueprint $table) {
            $table->dropColumn('status'); // drop string
        });
    }

    public function down(): void
    {
        Schema::table('vl_documents', function (Blueprint $table) {
            $table->string('status', 50)->nullable(); // restore if rollback
        });
    }
};
