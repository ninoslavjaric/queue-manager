<?php

namespace Nino\CustomQueueLaravel\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Nino\CustomQueueLaravel\Services\TaskI;

/**
 * @property $uuid string
 * @property $class_name string
 * @property $method string
 * @property $status string
 * @property $parameters array
 * @property $priority string
 * @property $delay int
 * @property $retries int
 * @property $pid int
 */
class CustomQueueTask extends Model implements TaskI
{
    protected $casts = [
        'parameters' => 'array',
    ];
    protected $primaryKey = 'uuid';
    protected $keyType = 'string';
    protected $fillable = [
        'uuid',
        'class_name',
        'method',
        'parameters',
        'status',
        'priority',
        'delay',
        'retries',
        'pid',
    ];

    public function getClassName(): string
    {
        return $this->class_name;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getDelay(): int
    {
        return $this->delay;
    }

    public function getRetries(): int
    {
        return $this->retries;
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function(self $item) {
            $item->uuid = Str::uuid()->toString();
        });
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getPid(): int
    {
        return $this->pid;
    }
}
