<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vl_projects', function (Blueprint $table) {

            // === REFERENSI BISNIS ===
            $table->string('reference', 50)
                ->nullable()
                ->after('oss_reference_id');

            // === KLASIFIKASI PROJECT ===
            $table->string('kategori', 50)
                ->nullable()
                ->after('project_type');

            $table->string('sub_category', 50)
                ->nullable()
                ->after('kategori');

            $table->string('segment_A', 30)
                ->nullable()
                ->after('sub_category');

            $table->string('paket', 30)
                ->nullable()
                ->after('segment_A');

            // === KOORDINAT ===
            $table->decimal('latitude', 10, 7)
                ->nullable()
                ->after('paket');

            $table->decimal('longitude', 10, 7)
                ->nullable()
                ->after('latitude');

            // === CATATAN TAMBAHAN ===
            $table->text('note')
                ->nullable()
                ->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('vl_projects', function (Blueprint $table) {
            $table->dropColumn([
                'reference',
                'kategori',
                'sub_category',
                'segment_A',
                'paket',
                'latitude',
                'longitude',
                'note',
            ]);
        });
    }
};
