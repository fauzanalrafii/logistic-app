<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('vl_projects')->insert([
            [
                'uuid' => Str::uuid(),
                'code' => 'PRJ001',
                'name' => 'Project Alpha',
                'planner_id' => 1, // pastikan user ID 1 ada
                'status' => 'On Progress',
            ],
            [
                'uuid' => Str::uuid(),
                'code' => 'PRJ002',
                'name' => 'Project Beta',
                'planner_id' => 2, // pastikan user ID 2 ada
                'status' => 'Planning',
            ],
        ]);

    }
}
