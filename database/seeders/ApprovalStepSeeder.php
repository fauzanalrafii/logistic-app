<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ApprovalStepSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil approval flow untuk project
        $approvalFlow = DB::table('vl_approval_flows')
            ->where('process_type', 'PLAN_ON_DESK_PLANNING')
            ->first();

        if (!$approvalFlow) {
            $this->command->warn('Approval flow not found. Run ApprovalFlowSeeder first.');
            return;
        }

        // Hapus step lama untuk flow ini
        DB::table('vl_approval_steps')
            ->where('approval_flow_id', $approvalFlow->id)
            ->delete();

        // 5 approval steps sesuai flowchart
        // required_role_id set ke null untuk sementara (development)
        DB::table('vl_approval_steps')->insert([
            [
                'uuid' => Str::uuid(),
                'approval_flow_id' => $approvalFlow->id,
                'step_order' => 1,
                'name' => 'Planning',
                'required_role_id' => 29,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'approval_flow_id' => $approvalFlow->id,
                'step_order' => 2,
                'name' => 'GM Operation',
                'required_role_id' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'approval_flow_id' => $approvalFlow->id,
                'step_order' => 3,
                'name' => 'GM Sales',
                'required_role_id' => 31,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'approval_flow_id' => $approvalFlow->id,
                'step_order' => 4,
                'name' => 'Dir. Operation',
                'required_role_id' => 32,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => Str::uuid(),
                'approval_flow_id' => $approvalFlow->id,
                'step_order' => 5,
                'name' => 'Dir. Marcomm',
                'required_role_id' => 33,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->command->info('Inserted 5 approval steps for project flow.');
    }
}
