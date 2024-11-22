<?php

namespace Nino\CustomQueueLaravel\Providers;

use Illuminate\Support\ServiceProvider;
use Nino\CustomQueueLaravel\Console\Commands\TaskBgExecutor;
use Nino\CustomQueueLaravel\Services\Logger\DefaultLogger;
use Nino\CustomQueueLaravel\Services\Logger\ErrorLogger;
use Nino\CustomQueueLaravel\Services\QueueManager;
use Illuminate\Support\Facades\Config;
use Nino\CustomQueueLaravel\Services\QueueManagerPayloadValidator;
use Nino\CustomQueueLaravel\Services\TaskI;

class CustomQueueProvider extends ServiceProvider
{

    private function alterConfigs(): void
    {
        Config::set(
            'logging.channels',
            array_merge(
                Config::get('logging.channels'),
                include __DIR__ . '/../../config/logging.channel.php',
            )
        );
        Config::set(
            'queue-manager',
            Config::get('queue-manager', []) +
            include __DIR__ . '/../../config/queue-manager.php',
        );
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->alterConfigs();

        $managerDriverClass = config('queue-manager.drivers')[config('queue-manager.driver')];

        $this->app->bind(TaskI::class, config('queue-manager.task-class'));
        $this->app->singleton(QueueManagerPayloadValidator::class, fn($app) => new QueueManagerPayloadValidator());
        $this->app->singleton(DefaultLogger::class, fn($app) => new DefaultLogger());
        $this->app->singleton(ErrorLogger::class, fn($app) => new ErrorLogger());
        $this->app->singleton(
            QueueManager::class,
            fn($app) => new $managerDriverClass(
                $app->get(ErrorLogger::class),
                $app->get(DefaultLogger::class),
                $app->get(QueueManagerPayloadValidator::class),
            )
        );

        $this->commands([
            TaskBgExecutor::class,
        ]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'nino-custom-queue');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../../routes/console.php');
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }
}
