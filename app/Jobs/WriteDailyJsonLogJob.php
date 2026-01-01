<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class WriteDailyJsonLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public string $level;
    public string $message;
    public array $context;

    /**
     * @param string $level   info|debug|warning|error
     * @param string $message pesan log
     * @param array  $context data context (harus array)
     */
    public function __construct(string $level, string $message, array $context = [])
    {
        $this->level = strtolower($level);
        $this->message = $message;
        $this->context = $context;
    }

    public function handle(): void
    {
        // Guard: pastikan level valid
        $allowed = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
        $level = in_array($this->level, $allowed, true) ? $this->level : 'info';

        // Tulis ke channel json kamu
        Log::channel('daily_json')->{$level}($this->message, $this->context);
    }
}
