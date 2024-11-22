<?php

namespace Nino\CustomQueueLaravel\Services\QueueManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Nino\CustomQueueLaravel\Models\CustomQueueTask;
use Nino\CustomQueueLaravel\Services\QueueManager;
use Nino\CustomQueueLaravel\Services\TaskI;

class Redis extends QueueManager
{

    protected function push(TaskI $task): void
    {
        // TODO: Implement push(TaskI $task) method.
    }

    public function pop(int $limit): array|Collection
    {
        // TODO: Implement pop(int $limit) method.
        return [];
    }

    public function getTaskByUuid(string $uuid): ?TaskI
    {
        // TODO: Implement getTaskByUuid() method.
        return null;
    }

    public function updateStatus(string $uuid, string $status): bool
    {
        // TODO: Implement updateStatus() method.
        return false;
    }

    public function updatePid(string $uuid, int $pid): bool
    {
        // TODO: Implement updatePid() method.
        return false;
    }

    public function getRunningTasks(): array|Collection
    {
        // TODO: Implement getRunningTasks() method.
        return [];
    }

    public function getTasks(): array|Collection
    {
        // TODO: Implement getTasks() method.
        return [];
    }
}
