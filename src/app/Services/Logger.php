<?php

namespace Nino\CustomQueueLaravel\Services;

use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

abstract class Logger implements LoggerInterface
{
    protected const DEFAULT_CHANNEL = 'custom_queue_tasks';
    protected const ERROR_CHANNEL = 'custom_queue_tasks_errors';

    protected string $channel;

    public function emergency(\Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->emergency($message, $context);
    }

    public function alert(\Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->alert($message, $context);
    }

    public function critical(\Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->critical($message, $context);
    }

    public function error(\Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->error($message, $context);
    }

    public function warning(\Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->warning($message, $context);
    }

    public function notice(\Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->notice($message, $context);
    }

    public function info(\Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->info($message, $context);
    }

    public function debug(\Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->debug($message, $context);
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        Log::channel($this->channel)->log($level, $message, $context);
    }
}
