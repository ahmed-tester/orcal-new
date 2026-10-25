<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Dedicated logger for the smart-import pipeline.
 *
 * Writes to the "test" channel when it is configured (storage/logs/test.log)
 * and falls back to the default channel otherwise, so a deployment that has
 * no "test" channel keeps working.
 */
class SmartImportLogger
{
    public const CHANNEL = 'test';

    public static function info(string $message, array $context = []): void
    {
        self::logger()->info($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::logger()->warning($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::logger()->error($message, $context);
    }

    protected static function logger(): LoggerInterface
    {
        return Log::channel(
            config('logging.channels.'.self::CHANNEL) ? self::CHANNEL : config('logging.default')
        );
    }
}
