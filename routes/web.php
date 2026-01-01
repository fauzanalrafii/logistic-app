<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\LogMonitorController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectHistoryController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Roles\RoleController;
use App\Http\Controllers\Roles\AssignRoleController;
use App\Http\Controllers\Roles\PermissionController;
use App\Http\Controllers\Roles\AssignPermissionController;
use App\Http\Controllers\ProjectPlanningController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\BoqOnDeskDocumentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\HelpController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use phpseclib3\Crypt\RSA\Formats\Keys\Raw;
use App\Jobs\TestRabbitJob;

Route::get('/', function () {
    return Auth::check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// Routes untuk guest (belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');

    // Submit Form
    Route::post('/login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    // Dashboard dengan RBAC Spatie
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Command Palette Search API
    Route::get('/api/search', [ProjectController::class, 'searchApi'])->name('api.search');

    // Profil Saya
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');

    Route::middleware(['auth', 'permission:manage_roles'])->group(function () {
        // Roles
        Route::resource('roles', RoleController::class);

        // Permissions
        Route::prefix('permissions')
            ->name('permissions.')
            ->group(function () {
                Route::get('/', [PermissionController::class, 'index'])->name('index');
                Route::get('/create', [PermissionController::class, 'create'])->name('create');
                Route::post('/', [PermissionController::class, 'store'])->name('store');

                Route::get('/{permission}/edit', [PermissionController::class, 'edit'])->name('edit');
                Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');

                Route::delete('/{permission}', [PermissionController::class, 'destroy'])->name('destroy');
            });

        // Assign Roles
        Route::prefix('assign-roles')
            ->name('assign-roles.')
            ->group(function () {
                Route::get('/', [AssignRoleController::class, 'index'])->name('index');
                Route::get('/create', [AssignRoleController::class, 'create'])->name('create');
                Route::post('/store', [AssignRoleController::class, 'store'])->name('store');

                Route::get('{id}/edit', [AssignRoleController::class, 'edit']);
                Route::put('{id}', [AssignRoleController::class, 'update']);
                Route::delete('{id}', [AssignRoleController::class, 'destroy']);
            });

        //Assign Permission
        Route::prefix('assign-permissions')
            ->name('assign-permissions.')
            ->group(function () {
                Route::get('/', [AssignPermissionController::class, 'index'])->name('index');
                Route::get('/create', [AssignPermissionController::class, 'create'])->name('create');
                Route::post('/', [AssignPermissionController::class, 'store'])->name('store');
                Route::get('{id}/edit', [AssignPermissionController::class, 'edit'])->name('edit');
                Route::put('{id}', [AssignPermissionController::class, 'update'])->name('update');
                Route::delete('{id}', [AssignPermissionController::class, 'destroy'])->name('destroy');
            });
    });

    // ==========================
    // APPROVAL (Atasan Planning)
    // ==========================
    Route::prefix('approval')->name('approval.')->middleware(['auth', 'permission:approval.view'])->group(function () {

        // inbox
        Route::get('/', [ApprovalController::class, 'index'])->name('inbox');

        Route::get('/history', [ApprovalController::class, 'history'])->name('history');

        // show detail (WAJIB pakai ID)
        Route::get('/{id}', [ApprovalController::class, 'show'])->whereNumber('id')->name('show');
        // aksi approve / reject (pisahin permission biar rapi)
        Route::post('/{id}/approve', [ApprovalController::class, 'approve'])
            ->whereNumber('id')
            ->middleware('permission:approval.approve')
            ->name('approve');

        Route::post('/{id}/reject', [ApprovalController::class, 'reject'])
            ->whereNumber('id')
            ->middleware('permission:approval.reject')
            ->name('reject');
    });

    // ==========================
    // APPROVAL WORKFLOW MANAGEMENT
    // ==========================
    Route::prefix('approval/workflows')
        ->name('approval.workflow.')
        ->middleware(['auth', 'permission:manage_roles'])
        ->group(function () {
            Route::get('/', [App\Http\Controllers\ApprovalWorkflowController::class, 'index'])->name('index');
            Route::get('/create', [App\Http\Controllers\ApprovalWorkflowController::class, 'create'])->name('create');
            Route::post('/', [App\Http\Controllers\ApprovalWorkflowController::class, 'store'])->name('store');
            Route::get('/{id}', [App\Http\Controllers\ApprovalWorkflowController::class, 'show'])->whereNumber('id')->name('show');
            Route::get('/{id}/edit', [App\Http\Controllers\ApprovalWorkflowController::class, 'edit'])->whereNumber('id')->name('edit');
            Route::put('/{id}', [App\Http\Controllers\ApprovalWorkflowController::class, 'update'])->whereNumber('id')->name('update');
            Route::delete('/{id}', [App\Http\Controllers\ApprovalWorkflowController::class, 'destroy'])->whereNumber('id')->name('destroy');
        });

    // -------------------------------------------------------------------------
    // Notification
    // -------------------------------------------------------------------------
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->middleware('auth')
        ->name('notifications.index');


    // -------------------------------------------------------------------------
    // Log Monitor
    // -------------------------------------------------------------------------
    Route::get('/logs/monitor', [LogMonitorController::class, 'index'])
        ->middleware(['auth', 'permission:logs_monitor.view'])
        ->name('log.monitor');

    // -------------------------------------------------------------------------
    // Documents
    // -------------------------------------------------------------------------
    Route::prefix('documents')
        ->name('documents.')
        ->group(function () {
            Route::get('/', [DocumentController::class, 'index'])->name('index');
            Route::get('/create', [DocumentController::class, 'create'])->name('create');
            Route::post('/', [DocumentController::class, 'store'])->name('store');
            Route::get('/{document}/view', [DocumentController::class, 'view'])->name('view');
            Route::get('/{document}/download', [DocumentController::class, 'download'])->name('download');
            Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
            Route::get('/{document}/replace-file', [DocumentController::class, 'replaceFile'])
                ->name('replaceFile');
            Route::put('/{document}/replace-file', [DocumentController::class, 'updateFile'])
                ->name('updateFile');
        });

    // -------------------------------------------------------------------------
    // Projects (custom pages)
    // -------------------------------------------------------------------------
    Route::prefix('projects')->name('projects.')->group(function () {
        Route::get('/plan-on-desk', [ProjectController::class, 'planOnDesk'])->name('plan_on_desk');
        Route::delete('/plan-on-desk/{id}', [ProjectController::class, 'softDelete'])->name('plan_on_desk.delete');
        Route::get('/sla-overdue', [ProjectController::class, 'slaOverdue'])->name('slaOverdue'); // kalau method kamu slaOverdue, sesuaikan
        Route::get('/planning', [ProjectPlanningController::class, 'index'])->name('planning');
        Route::post('/planning/boq', [ProjectPlanningController::class, 'storeBoq'])->name('planning.boq.store');
        Route::post('/planning/bep', [ProjectPlanningController::class, 'storeBep'])->name('planning.bep.store');
        Route::post('/planning/revise', [ProjectPlanningController::class, 'revise'])->name('planning.revise'); // revise planning
        Route::post('/planning/submit', [ProjectPlanningController::class, 'submit'])->name('planning.submit');
        Route::post('/planning/revise', [ProjectPlanningController::class, 'revise'])->name('planning.revise');
        Route::get('/project-detail', [ProjectController::class, 'detail'])->name('detail');
        Route::get('/history', [ProjectHistoryController::class, 'index'])->name('history');
    });

    // -------------------------------------------------------------------------
    // Projects (resource) - jangan biarkan "planning" ketangkep jadi {project}
    // -------------------------------------------------------------------------
    // -------------------------------------------------------------------------
    // Projects (CRUD) - permission based (read-only untuk approver)
    // -------------------------------------------------------------------------
    Route::prefix('projects')->name('projects.')->group(function () {

        // LIST (Project Board) - hanya yang punya permission view
        Route::get('/', [ProjectController::class, 'index'])
            ->name('index');

        // CREATE
        Route::get('/create', [ProjectController::class, 'create'])
            ->name('create');

        Route::get('/project-detail', [ProjectController::class, 'detail'])
            ->middleware('permission:project.view|approval.view')
            ->name('detail');

        Route::post('/', [ProjectController::class, 'store'])
            ->name('store');

        // EDIT / UPDATE (ini yang bikin approver gak bisa edit)
        Route::get('/{project}/edit', [ProjectController::class, 'edit'])
            ->whereNumber('project')
            ->middleware('permission:project.edit')
            ->name('edit');

        Route::put('/{project}', [ProjectController::class, 'update'])
            ->whereNumber('project')
            ->middleware('permission:project.edit')
            ->name('update');

        // DELETE
        Route::delete('/{project}', [ProjectController::class, 'destroy'])
            ->whereNumber('project')
            ->middleware('permission:project.delete')
            ->name('destroy');
    });

    // -------------------------------------------------------------------------
    // Audit Logs
    // -------------------------------------------------------------------------
    Route::get('/audit-logs', [AuditLogController::class, 'index'])
        ->middleware(['auth', 'permission:audit_logs.view'])
        ->name('audit-logs.index');

    // -------------------------------------------------------------------------
    // User Settings & Help
    // -------------------------------------------------------------------------
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::get('/help', [HelpController::class, 'index'])->name('help');

    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // routes/web.php
    Route::post(
        '/documents/boq-ondesk/generate',
        [BoqOnDeskDocumentController::class, 'generate']
    );
});

Route::fallback(function () {
    return redirect('/dashboard');
});
