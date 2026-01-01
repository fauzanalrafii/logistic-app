<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vl_projects', function (Blueprint $table) {

            // snapshot lokasi dari master_kodepos_new
            $table->string('zipcode', 10)
                ->nullable()
                ->after('area');

            $table->string('province', 60)
                ->nullable()
                ->after('zipcode');

            $table->string('city', 60)
                ->nullable()
                ->after('province');

            $table->string('district', 60)
                ->nullable()
                ->after('city');

            $table->string('sub_district', 60)
                ->nullable()
                ->after('district');

            // regional / area besar (sesuai project_mgt)
            $table->string('region', 30)
                ->nullable()
                ->after('sub_district');

            // reference ringan ke master (tanpa FK)
            $table->unsignedBigInteger('kodepos_id')
                ->nullable()
                ->after('region');
        });
    }

    public function down(): void
    {
        Schema::table('vl_projects', function (Blueprint $table) {
            $table->dropColumn([
                'zipcode',
                'province',
                'city',
                'district',
                'sub_district',
                'region',
                'kodepos_id',
            ]);
        });
    }
};
