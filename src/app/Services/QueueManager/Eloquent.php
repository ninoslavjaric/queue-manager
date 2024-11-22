<?php

namespace Nino\CustomQueueLaravel\Services\QueueManager;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Nino\CustomQueueLaravel\Models\CustomQueueTask;
use Nino\CustomQueueLaravel\Services\Logger\DefaultLogger;
use Nino\CustomQueueLaravel\Services\Logger\ErrorLogger;
use Nino\CustomQueueLaravel\Services\QueueManager;
use Nino\CustomQueueLaravel\Services\QueueManagerPayloadValidator;
use Nino\CustomQueueLaravel\Services\TaskI;

class Eloquent extends QueueManager
{
    private Model $model;

    public function __construct(ErrorLogger $errorLogger, DefaultLogger $defaultLogger, QueueManagerPayloadValidator $validator)
    {
        parent::__construct($errorLogger, $defaultLogger, $validator);
        $this->setModel(app(CustomQueueTask::class));
    }

    /**
     * @param Model $model
     */
    public function setModel(Model $model): void
    {
        $this->model = $model;
    }

    protected function push(TaskI $task): void
    {
        CustomQueueTask::create($task->toArray());
    }

    /**
     * @param int $limit
     * @return array|Collection [TaskI]
     */
    public function pop(int $limit): array|Collection
    {
        $limit -= $this->model->where('status', 'running')->count();

        return $this->model->where('status', 'idle')->orderBy('priority', 'desc')->orderBy('created_at')->limit($limit)->get();
    }

    public function getTaskByUuid(string $uuid): ?TaskI
    {
        return $this->model->find($uuid);
    }

    public function updateStatus(string $uuid, string $status): bool
    {
        return $this->model->find($uuid)->update(['status' => $status]);
    }

    public function updatePid(string $uuid, int $pid): bool
    {
        return $this->model->find($uuid)->update(['pid' => $pid]);
    }

    public function getRunningTasks(): array|Collection
    {
        return $this->model->where('status', QueueManager::RUNNING)->get();
    }

    public function getTasks(): array|Collection
    {
        return $this->model->all(); // this is not the happiest solution. paging needed
    }
}
