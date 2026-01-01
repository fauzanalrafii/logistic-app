<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil salah satu project sebagai contoh
        $projectId = DB::table('vl_projects')->first()->id ?? 1;

        DB::table('vl_assets')->insert([
            [
                'uuid' => Str::uuid(),
                'project_id' => $projectId,
                'asset_type' => 'Laptop',
                'code' => 'AST-001',
                'status' => 'available',
            ],
            [
                'uuid' => Str::uuid(),
                'project_id' => $projectId,
                'asset_type' => 'Projector',
                'code' => 'AST-002',
                'status' => 'in_use',
            ],
            [
                'uuid' => Str::uuid(),
                'project_id' => $projectId,
                'asset_type' => 'Server',
                'code' => 'AST-003',
                'status' => 'maintenance',
            ],
        ]);
    }
}
