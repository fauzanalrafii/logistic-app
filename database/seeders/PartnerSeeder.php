<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnerSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vl_partners')->insert([
            [
                'uuid' => Str::uuid(),
                'name' => 'Partner A',
                'code' => 'PRT001',
                'type' => 'supplier',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Partner B',
                'code' => 'PRT002',
                'type' => 'client',
            ],
            [
                'uuid' => Str::uuid(),
                'name' => 'Partner C',
                'code' => 'PRT003',
                'type' => 'supplier',
            ],
        ]);
    }
}
