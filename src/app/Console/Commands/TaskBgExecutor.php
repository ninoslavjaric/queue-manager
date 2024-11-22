<?php

namespace Nino\CustomQueueLaravel\Console\Commands;

use Illuminate\Console\Command;
use Nino\CustomQueueLaravel\Models\CustomQueueTask;
use Nino\CustomQueueLaravel\Services\Logger\DefaultLogger;
use Nino\CustomQueueLaravel\Services\Logger\ErrorLogger;
use Nino\CustomQueueLaravel\Services\Logger\Loggable;
use Nino\CustomQueueLaravel\Services\QueueManager;
use Symfony\Component\Console\Input\InputArgument;

class TaskBgExecutor extends Command
{
    use Loggable;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'custom-queue:task-bg-execute';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Executes task in background';
    private QueueManager $manager;

    public function __construct(QueueManager $manager, ErrorLogger $errorLogger, DefaultLogger $defaultLogger)
    {
        parent::__construct();
        $this->setDefaultLogger($defaultLogger);
        $this->setErrorLogger($errorLogger);

        $this->manager = $manager;
    }

    protected function configure()
    {
        parent::configure();

        $this->addArgument('task-uuid', InputArgument::REQUIRED, 'Unique id of task');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uuid = $this->argument('task-uuid');
        $logContext = ['flag' => $this->signature, 'uuid' => $uuid];

        $task = $this->manager->getTaskByUuid($uuid);

        if (empty($task)) {
            $this->error_log($this->formatMessage($logContext, "The task doesn't exist."));
            return;
        }

        if (
            $task->getStatus() != QueueManager::IDLE
            && !is_null($task->getPid())
            && $task->getPid() != getmypid()
        ) {
            $this->error_log($this->formatMessage($logContext, "The task is already running or is finished."));
            return;
        }

        $this->manager->updateStatus($uuid, QueueManager::RUNNING);
        $this->manager->updatePid($uuid, getmypid());

        $logContext = array_merge($logContext, ['pid' => getmypid()]);

        try {
            $result = $this->manager->runBackgroundJob(
                class: $task->getClassName(),
                method: $task->getMethod(),
                params: $task->getParameters(),
                delay: $task->getDelay(),
                retries: $task->getRetries(),
                logContext: $logContext,
            );
            $result
                ? $this->log($this->formatMessage([$this->signature, $uuid], "Task succeeded"))
                : $this->error_log($this->formatMessage([$this->signature, $uuid], "Task failed"));

            $this->manager->updateStatus($uuid, $result ? QueueManager::FINISHED : QueueManager::FAILED);
        } catch (\Exception $e) {
            $this->error_log($this->formatMessage([$this->signature, $uuid], $e->getMessage()));
            $this->manager->updateStatus($uuid, QueueManager::FAILED);
        }


    }
}
