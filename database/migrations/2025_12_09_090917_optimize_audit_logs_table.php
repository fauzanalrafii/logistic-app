<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('vl_audit_log', function (Blueprint $table) {
            $table->index('table_name'); // Mempercepat Filter Target
            $table->index('action');     // Mempercepat Filter Aksi
            $table->index('user_id');    // Mempercepat loading User
            $table->index('created_at'); // Mempercepat Filter Tanggal
        });

        DB::statement("
            ALTER TABLE vl_audit_log
            ADD COLUMN search_vector LONGTEXT
            GENERATED ALWAYS AS (
                LOWER(CONCAT(
                    IFNULL(table_name, ''), ' ',
                    IFNULL(record_id, ''), ' ',
                    IFNULL(CAST(old_values AS CHAR), ''), ' ',
                    IFNULL(CAST(new_values AS CHAR), '')
                ))
            ) STORED
        ");

        DB::statement("CREATE FULLTEXT INDEX idx_audit_fulltext ON vl_audit_log(search_vector)");
    }

    public function down()
    {
        Schema::table('vl_audit_log', function (Blueprint $table) {
            $table->dropIndex(['table_name']);
            $table->dropIndex(['action']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            
            $table->dropIndex('idx_audit_fulltext');
            $table->dropColumn('search_vector');
        });
    }
};
