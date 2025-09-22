<?php

namespace App\Infrastructure\Logging;

use App\Domain\Contracts\LoggerInterface;

class FileLogger implements LoggerInterface
{
    private string $logPath;
    private string $dateFormat;

    public function __construct(string $logPath = null, string $dateFormat = 'Y-m-d H:i:s')
    {
        $this->logPath = $logPath ?: __DIR__ . '/../../../logs/app.log';
        $this->dateFormat = $dateFormat;

        $this->ensureLogDirectoryExists();
    }

    private function ensureLogDirectoryExists(): void
    {
        $dir = dirname($this->logPath);

        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                $this->logPath = sys_get_temp_dir() . '/news_admin.log';
                error_log("Cannot create log directory. Using: " . $this->logPath);
            }
        }

        if (!is_writable(dirname($this->logPath))) {
            $this->logPath = sys_get_temp_dir() . '/news_admin.log';
            error_log("Log directory not writable. Using: " . $this->logPath);
        }
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->writeLog('EMERGENCY', $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->writeLog('ALERT', $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->writeLog('CRITICAL', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->writeLog('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->writeLog('WARNING', $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->writeLog('NOTICE', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->writeLog('INFO', $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->writeLog('DEBUG', $message, $context);
    }

    private function writeLog(string $level, string $message, array $context = []): void
    {
        try {
            $timestamp = date($this->dateFormat);
            $contextStr = !empty($context) ? json_encode($context) : '';

            $logEntry = sprintf(
                "[%s] %s: %s %s\n",
                $timestamp,
                $level,
                $message,
                $contextStr
            );

            file_put_contents($this->logPath, $logEntry, FILE_APPEND | LOCK_EX);

        } catch (\Exception $e) {
            error_log("Log write failed: " . $e->getMessage());
        }
    }

    public function getLogs(int $lines = 100): array
    {
        if (!file_exists($this->logPath)) {
            return [];
        }

        $file = new \SplFileObject($this->logPath, 'r');
        $file->seek(PHP_INT_MAX);
        $lastLine = $file->key();

        $lines = min($lines, $lastLine + 1);
        $file->seek(max(0, $lastLine - $lines + 1));

        $logEntries = [];
        for ($i = 0; $i < $lines; $i++) {
            $logEntries[] = $file->current();
            $file->next();
        }

        return array_reverse($logEntries);
    }
}