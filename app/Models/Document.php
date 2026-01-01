<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Traits\AppActivityLoggable;
use App\Models\Status;
use App\Models\Project;
use App\Traits\Auditable;


class Document extends Model
{
    use AppActivityLoggable;
    use Auditable;
    protected $table = 'vl_documents';

    protected $fillable = [
        'uuid',
        'project_id',
        'related_type',
        'related_id',
        'document_type',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'status_id',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];


    public function project()
    {
        // vl_documents.project_id -> vl_projects.id
        return $this->belongsTo(Project::class, 'project_id', 'id');
    }

    // vl_documents.status_id -> vl_status.id
     public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }
    
    // auto generate UUID setiap create
    protected static function booted()
    {
        static::creating(function (Document $doc) {
            if (empty($doc->uuid)) {
                $doc->uuid = (string) Str::uuid();
            }

            if (empty($doc->uploaded_by) && Auth::check()) {
            $auth = Auth::user();
            $doc->uploaded_by = $auth?->ID ?? $auth?->id ?? null;
            }
        });
    }
}
