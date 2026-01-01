<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;

class JsonFormatterLogger
{
    /**
     * Ubah logger Laravel supaya pakai JSON formatter.
     */
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            // Set formatter jadi JSON satu baris per log
            $handler->setFormatter(new JsonFormatter());
        }
    }
}
