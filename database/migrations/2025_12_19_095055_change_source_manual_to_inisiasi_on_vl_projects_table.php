<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Update data dulu: MANUAL -> INISIASI
        DB::table('vl_projects')
            ->where('source', 'MANUAL')
            ->update(['source' => 'INISIASI']);

        // 2) Ubah ENUM definition
        //    (MySQL butuh ALTER ... MODIFY untuk enum)
        DB::statement("
            ALTER TABLE vl_projects
            MODIFY source ENUM('OSS','INISIASI')
            NOT NULL
            DEFAULT 'INISIASI'
        ");
    }

    public function down(): void
    {
        // 1) Balikin enum dulu supaya value MANUAL valid lagi
        DB::statement("
            ALTER TABLE vl_projects
            MODIFY source ENUM('OSS','MANUAL')
            NOT NULL
            DEFAULT 'MANUAL'
        ");

        // 2) Balikin data: INISIASI -> MANUAL
        DB::table('vl_projects')
            ->where('source', 'INISIASI')
            ->update(['source' => 'MANUAL']);
    }
};
