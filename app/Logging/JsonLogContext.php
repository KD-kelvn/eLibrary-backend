<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\LogRecord;

/**
 * Tap for the JSON log channel (family-hub pattern): one JSON object per line
 * with service (api|worker|scheduler|console) and environment. Request id
 * arrives via Context (AssignRequestId).
 */
final class JsonLogContext
{
    public function __invoke(Logger $logger): void
    {
        $service = self::service();
        $environment = app()->environment();
        $monolog = $logger->getLogger();

        if (! $monolog instanceof \Monolog\Logger) {
            return;
        }

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter(includeStacktraces: true));
            }
        }

        $monolog->pushProcessor(fn (LogRecord $record): LogRecord => $record->with(
            extra: [...$record->extra, 'service' => $service, 'env' => $environment],
        ));
    }

    private static function service(): string
    {
        if (! app()->runningInConsole()) {
            return 'api';
        }

        $argv = $_SERVER['argv'] ?? [];
        $command = is_array($argv) && isset($argv[1]) && is_string($argv[1]) ? $argv[1] : '';

        return match ($command) {
            'queue:work', 'queue:listen' => 'worker',
            'schedule:run', 'schedule:work' => 'scheduler',
            default => 'console',
        };
    }
}
