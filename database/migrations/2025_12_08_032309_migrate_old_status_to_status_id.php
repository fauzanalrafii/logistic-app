<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ambil daftar status dari vl_status
        $statuses = DB::table('vl_status')->where('type', 'project')->pluck('id', 'name');

        // update vl_projects
        DB::table('vl_projects')->get()->each(function ($project) use ($statuses) {
            if ($project->status && isset($statuses[$project->status])) {
                DB::table('vl_projects')
                    ->where('id', $project->id)
                    ->update(['status_id' => $statuses[$project->status]]);
            }
        });
    }

    public function down(): void
    {
        // rollback tidak mengembalikan data
    }
};
