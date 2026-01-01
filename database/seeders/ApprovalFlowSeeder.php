<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApprovalFlowSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vl_approval_flows')->insert([
            [
                'uuid' => Str::uuid(),
                'name' => 'Approval BOQ + BEP Plan On Desk',
                'process_type' => 'PLAN_ON_DESK_PLANNING',
            ],
        ]);
    }
}
