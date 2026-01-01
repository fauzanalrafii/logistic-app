<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {

        // 1. DEFINE PERMISSIONS
        $permissions = [
            // Planning
            'create_boq',
            'input_survey',
            'update_konstruksi',

            // Sales
            'request_pembangunan',

            // Planning Approval
            'verifikasi_project',
            'validasi_project',

            // GM Sales
            'approve_project',

            // Procurement
            'approve_pp',
            'approve_mos',

            // Inventory
            'validate_go_live',
            'finalize_bast',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        //2. DEFINE ROLES
        $admin            = Role::firstOrCreate(['name' => 'admin']);
        $planning         = Role::firstOrCreate(['name' => 'planning']);
        $sales            = Role::firstOrCreate(['name' => 'sales']);
        $planningApproval = Role::firstOrCreate(['name' => 'planning_approval']);
        $gmSales          = Role::firstOrCreate(['name' => 'gm_sales']);
        $procurement      = Role::firstOrCreate(['name' => 'procurement']);
        $inventory        = Role::firstOrCreate(['name' => 'inventory']);

        //3. ASSIGN PERMISSIONS TO ROLES
        
        // Admin memiliki semua permission
        $admin->syncPermissions(Permission::all());

        // Planning
        $planning->syncPermissions([
            'create_boq',
            'input_survey',
            'update_konstruksi',
        ]);

        // Sales
        $sales->syncPermissions([
            'request_pembangunan',
        ]);

        // Planning Approval
        $planningApproval->syncPermissions([
            'verifikasi_project',
            'validasi_project',
        ]);

        // GM Sales
        $gmSales->syncPermissions([
            'approve_project',
        ]);

        // Procurement
        $procurement->syncPermissions([
            'approve_pp',
            'approve_mos',
        ]);

        // Inventory
        $inventory->syncPermissions([
            'validate_go_live',
            'finalize_bast',
        ]);
    }
}
