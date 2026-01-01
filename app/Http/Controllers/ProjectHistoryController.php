<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectStatusHistory;
use App\Models\Status;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectHistoryController extends Controller
{
    public function index(Request $request)
    {
        $search  = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 10);

        // =========================
        // 1) HISTORY PERUBAHAN STATUS
        // =========================
        $statusHistories = ProjectStatusHistory::query()
            ->with([
                'project:id,name,code,status_id,planner_id',
                'changer:ID,Name',
                'oldStatusData:id,name,type',
                'newStatusData:id,name,type',
            ])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    // Cari berdasarkan project
                    $qq->whereHas('project', function ($p) use ($search) {
                        $p->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });

                    // Cari berdasarkan user pengubah
                    $qq->orWhereHas('changer', function ($u) use ($search) {
                        $u->where('Name', 'like', "%{$search}%");
                    });

                    // ✅ Cari berdasarkan status lama/baru (relasi id->name)
                    $qq->orWhereHas('oldStatusData', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    });
                    $qq->orWhereHas('newStatusData', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    });

                    // ✅ Fallback tambahan untuk data history lama yang tersimpan sebagai string
                    $qq->orWhere('old_status', 'like', "%{$search}%")
                        ->orWhere('new_status', 'like', "%{$search}%");

                    // Catatan
                    $qq->orWhere('note', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('changed_at')
            ->paginate($perPage, ['*'], 'hist_page')
            ->withQueryString();

        // =========================
        // 2) PROJECT SELESAI/CLOSED
        // =========================
        $completedProjects = Project::query()
            ->notDeleted()
            ->with([
                'planner:ID,Name',
                'status:id,name,type',
            ])
            ->whereHas('status', function ($s) {
                $s->whereIn('name', ['SELESAI', 'CLOSED']);
            })
            ->when($search, function ($q) use ($search) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('area', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('project_type', 'like', "%{$search}%");

                    // ✅ Cari planner
                    $qq->orWhereHas('planner', function ($p) use ($search) {
                        $p->where('Name', 'like', "%{$search}%");
                    });

                    // ✅ Cari status name juga
                    $qq->orWhereHas('status', function ($s) use ($search) {
                        $s->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'done_page')
            ->withQueryString();

        return Inertia::render('projects/History', [
            'currentPage'       => 'project.history',
            'filters'           => [
                'search'   => $search,
                'per_page' => $perPage,
            ],
            'statusHistories'   => $statusHistories,
            'completedProjects' => $completedProjects,
        ]);
    }
}
