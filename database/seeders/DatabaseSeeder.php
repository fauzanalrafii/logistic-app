<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seeder harus dijalankan sesuai urutan relasi
        $this->call([
            UserSeeder::class,             // harus duluan, karena RolePermission bisa pakai user
            RolePermissionSeeder::class,   // role & permission
            ProjectSeeder::class,          // project
            PartnerSeeder::class,          // partner
            ApprovalFlowSeeder::class,     // approval flows
            ApprovalStepSeeder::class,
            StatusSeeder::class,     // approval steps (butuh approval flows & roles)
            AssetSeeder::class,            // assets (butuh projects)
        ]);
    }
}
