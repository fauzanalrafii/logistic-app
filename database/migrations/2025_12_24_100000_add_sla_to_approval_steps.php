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
        Schema::table('vl_approval_steps', function (Blueprint $table) {
            $table->integer('sla_hours')->nullable()->after('required_role_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vl_approval_steps', function (Blueprint $table) {
            $table->dropColumn('sla_hours');
        });
    }
};
