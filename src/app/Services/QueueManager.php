<?php

namespace Nino\CustomQueueLaravel\Services;

use Illuminate\Database\Eloquent\Collection;
use Nino\CustomQueueLaravel\Services\Logger\DefaultLogger;
use Nino\CustomQueueLaravel\Services\Logger\ErrorLogger;
use Nino\CustomQueueLaravel\Services\Logger\Loggable;
use Psr\Container\ContainerExceptionInterface;
use Illuminate\Support\Facades\Process;
use Psr\Log\LogLevel;
use function Illuminate\Support\php_binary;
use Symfony\Component\Process\Process as SymProcess;

abstract class QueueManager
{
    use Loggable;

    public const PRIORITY_LOW = 1;
    public const PRIORITY_NORMAL = 2;
    public const PRIORITY_HIGH = 3;

    public const PRIORITIES = [
        self::PRIORITY_LOW,
        self::PRIORITY_NORMAL,
        self::PRIORITY_HIGH,
    ];
    const IDLE = 'idle';
    const RUNNING = 'running';
    const CANCELLED = 'cancelled';
    const FAILED = 'failed';
    const FINISHED = 'finished';
    const STATUSES = [
        self::IDLE,
        self::RUNNING,
        self::CANCELLED,
        self::FAILED,
        self::FINISHED,
    ];

    private QueueManagerPayloadValidator $validator;
    public function __construct(ErrorLogger $errorLogger, DefaultLogger $defaultLogger, QueueManagerPayloadValidator $validator)
    {
        $this->setErrorLogger($errorLogger);
        $this->setDefaultLogger($defaultLogger);
        $this->setValidator($validator);
    }

    abstract public function getTaskByUuid(string $uuid): ?TaskI;

    abstract public function updateStatus(string $uuid, string $status): bool;

    abstract public function updatePid(string $uuid, int $pid): bool;
    abstract protected function push(TaskI $task): void;

    protected function setValidator(QueueManagerPayloadValidator $validator): void
    {
        $this->validator = $validator;
    }


    public function append(TaskI $task): void
    {
        try {
            $this->validator->validate($task);
            $this->push($task);
        } catch (ContainerExceptionInterface $e) {
            $this->error_log("Probably DI issue. Some param is expected to be autowired but isn't injected [{$e->getMessage()}]");
        } catch (QueueManagerPayloadException $e) {
            $this->error_log($e->getMessage(), \Psr\Log\LogLevel::WARNING);
        } catch (\Exception $e) {
            $this->error_log($e->getMessage());
        }
    }

    /**
     * @param int $limit
     * @return array|Collection [TaskI]
     */
    abstract public function pop(int $limit): array|Collection;

    /**
     * Run a background job.
     *
     * @param string $class
     * @param string $method
     * @param array $params
     * @param int $delay
     * @param int $retries
     * @return bool
     */
    public function runBackgroundJob(
        string $class,
        string $method,
        array  $params = [],
        int    $delay = 0,
        int    $retries = 0,
        array  $logContext = [],
    ): bool
    {
        try {
            $classMeta = new \ReflectionClass($class);

            $diParams = collect($classMeta->getConstructor()?->getParameters())->map(
                fn(\ReflectionParameter $parameter) => app($parameter->getType()->getName())
            )->toArray();

            $object = new $class(...$diParams);

            $logContext = array_merge($logContext, [
                'class' => $class,
                'method' => $method,
                'params' => $params,
                'delay' => $delay,
                'retries' => $retries,
            ]);

            $result = $object->$method(...$params);

            return is_null($result) || $result;
        } catch (\ReflectionException $e) {
            $this->errorLogger->alert($this->formatMessage($logContext, $e->getMessage()));
        } catch (\Exception $e) {

            if ($retries > 0) {
                $this->errorLogger->warning($this->formatMessage($logContext, $e->getMessage()));

                sleep($delay);

                $this->defaultLogger->info($this->formatMessage($logContext, 'Retrying'));
                return $this->runBackgroundJob(
                    class: $class,
                    method: $method,
                    params: $params,
                    delay: $delay,
                    retries: --$retries,
                    logContext: $logContext,
                );
            }

            $this->errorLogger->alert($this->formatMessage($logContext, $e->getMessage()));
        }

        return false;
    }

    private function parseProcesslist(string $content, string $platform): array
    {
        if ($platform == 'windows') {
            return $this->parseWinProcesslist($content);
        }

        if ($platform == 'linux') {
            return $this->parseLinuxProcesslist($content);
        }

        return [];
    }

    private function parseLinuxProcesslist(string $content): array
    {
        $processes = array_map(fn($process) => trim($process), explode(PHP_EOL, $content));
        $processes = array_filter(
            $processes, fn($process) => str_contains($process, 'artisan custom-queue:task-bg-execute')
            && !str_contains($process, 'grep')
        );

        return array_map(function ($process) {
            if (!preg_match('/^(\d+)\s.+\s(.+)$/', $process, $match)) {
                return [];
            }
            return array_slice($match, -2);
        }, $processes);
    }

    private function parseWinProcesslist(string $content): array
    {
        $processes = preg_replace('/^([^\s]+)/m', "@SEPARATOR@$1", $content);
        $processes = explode("@SEPARATOR@", $processes);
        $processes = array_map(fn($process) => trim(preg_replace('/\s+/', ' ', $process)), $processes);
        $processes = array_filter(
            $processes, fn($process) => str_starts_with($process, 'php.exe') && str_contains($process, 'custom-queue:task-bg-execute')
        );

        return array_map(function ($process) {
            if (!preg_match('/^php\.exe\s(\d+)\s.+\s(.+)$/', $process, $match)) {
                return [];
            }
            return array_slice($match, -2);
        }, $processes);
    }

    public function getRunningSysTasks(): array
    {
        $commandMap = collect([
            'linux' => 'ps aux | grep "artisan custom-queue:task-bg-execute"',
            'windows' => 'powershell -Command "Get-CimInstance Win32_Process | Select-Object Name, ProcessId, CommandLine | Format-Table -Wrap -AutoSize"'
        ]);

        $platform = strtolower(PHP_OS_FAMILY);

        if (!$commandMap->has($platform)) {
            $this->error_log($this->formatMessage([], 'Platform not supported'));
            return [];
        }

        $command = $commandMap->get($platform);
        $this->log("Getting running tasks [{$command}]", LogLevel::DEBUG);
        $run = Process::run($commandMap->get($platform));

        if ($run->failed()) {
            $this->error_log($this->formatMessage([], $run->errorOutput()));
            return [];
        }
        return $this->parseProcesslist($run->output(), $platform);
    }

    abstract public function getRunningTasks(): array|Collection;

    public static function queueDaemonFunction(): \Closure
    {
        return function (QueueManager $manager) {
            $logContext = ['flag' => 'custom-queue:daemon', 'queuePoolLimit' => config('queue-manager.queue-pool-limit')];
            $manager->log($manager->formatMessage($logContext, 'Daemon starting'));


            $runningUuids = array_column($manager->getRunningSysTasks(), 1);

            $manager->log(
                $manager->formatMessage($logContext, 'Cancelling tasks that aren\'t running in system')
            );
            $manager->getRunningTasks()
                ->filter(fn(TaskI $task) => !in_array($task->getUuid(), $runningUuids))
                ->each(fn(TaskI $task) => $manager->updateStatus($task->getUuid(), QueueManager::CANCELLED));

            $tasks = $manager->pop(intval(config('queue-manager.queue-pool-limit')));

            $logContext['tasksExtractedCount'] = $tasks->count();
            $manager->log($manager->formatMessage($logContext, "Preparing tasks for execution"));
            $asyncCallback = function (TaskI $task) use ($logContext, $manager) {
                $process = new SymProcess([
                    php_binary(),
                    base_path(defined('ARTISAN_BINARY') ? ARTISAN_BINARY : 'artisan'),
                    'custom-queue:task-bg-execute',
                    $task->getUuid(),
                ]);

                $process->setOptions(['create_new_console' => true]);
                $process->disableOutput();
                $process->start();
                $logContext['cmd'] = $process->getCommandLine();
                $manager->log($manager->formatMessage($logContext, 'Running task in background'));
            };
            $tasks->each($asyncCallback);
        };
    }

    /**
     * @return array|Collection[TaskI]
     */
    abstract public function getTasks(): array|Collection;

    public function cancelTask(string $uuid): void
    {
        $task = $this->getTaskByUuid($uuid);
        $runningPids = array_column($this->getRunningSysTasks(), 0);

        if (!is_null($task->getPid()) && in_array($task->getPid(), $runningPids)) {
            Process::run(['kill', $task->getPid()]);
        }
        $this->updateStatus($uuid, self::CANCELLED);
    }
}
