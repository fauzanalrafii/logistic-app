<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class CleanupOldLogs extends Command
{
    protected $signature = 'logs:cleanup';
    protected $description = 'Hapus file log lebih dari 14 hari';

    public function handle()
    {
        $path = storage_path('logs');

        if (!File::exists($path)) {
            $this->error("Folder logs tidak ditemukan");
            return;
        }

        $files = File::glob($path . DIRECTORY_SEPARATOR . 'log-*.log');

        if (empty($files)) {
            $this->info("Tidak ada file log ditemukan");
            return;
        }

        foreach ($files as $file) {
            $lastModified = Carbon::createFromTimestamp(filemtime($file));

            // DEBUG output
            $this->line(
                basename($file) .
                ' | modified: ' .
                $lastModified->toDateTimeString()
            );

            if ($lastModified->lt(now()->subDays(14))) {
                File::delete($file);
                $this->info("Deleted: " . basename($file));
            }
        }
    }
}
