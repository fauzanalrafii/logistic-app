<?php

namespace App\Http\Controllers;

use App\Logging\AppLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;

class LogMonitorController extends Controller
{
    public function index(Request $request)
    {
        // ✅ sekarang via RabbitMQ (bukan nulis langsung)
        AppLogger::activity('log_monitor_accessed', [
            'date_param' => $request->input('date'),
        ]);

        $dateInput = $request->input('date');
        $date = $dateInput
            ? Carbon::createFromFormat('Y-m-d', $dateInput)
            : now();

        $dateForFilename = $date->format('Y-m-d');

        // ⚠️ ini tetap: cari file log berdasarkan tanggal
        $pattern = storage_path("logs/*{$dateForFilename}*.log");
        $matchingFiles = glob($pattern);

        $filePath = $matchingFiles[0] ?? null;
        $fileExists = $filePath && File::exists($filePath);
        $logs = [];

        if ($fileExists) {
            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                $decoded = json_decode($line, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $logs[] = [
                        'datetime' => $decoded['datetime'] ?? '',
                        'level'    => $decoded['level_name'] ?? ($decoded['level'] ?? ''),
                        'message'  => $decoded['message'] ?? '',
                        'context'  => $decoded['context'] ?? [],
                    ];
                }
            }
        }

        $contextKeys = [
            'action',
            'ip',
            'method',
            'time',
            'url',
            'user_agent',
            'user_id',
            'user_name',
            'user_uid',
        ];

        return Inertia::render('logs/monitor', [
            'logs'        => $logs,
            'date'        => $date->format('Y-m-d'),
            'fileExists'  => $fileExists,
            'fileName'    => $fileExists ? basename($filePath) : "log_{$dateForFilename}.log",
            'contextKeys' => $contextKeys,
        ]);
    }
}
