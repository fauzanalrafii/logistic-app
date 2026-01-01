<?php

namespace App\Jobs;

use App\Models\ProjectStatusHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class StoreProjectStatusHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $projectId;
    public ?int $oldStatus;
    public int $newStatus;
    public ?int $changedBy;
    public string $note;

    public function __construct(
        int $projectId,
        ?int $oldStatus,
        int $newStatus,
        ?int $changedBy,
        string $note
    ) {
        $this->projectId = $projectId;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->changedBy = $changedBy;
        $this->note = $note;
    }

    public function handle(): void
    {
        ProjectStatusHistory::create([
            'uuid'       => Str::uuid(),
            'project_id' => $this->projectId,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'changed_by' => $this->changedBy,
            'changed_at' => now(),
            'note'       => $this->note,
        ]);
    }
}
