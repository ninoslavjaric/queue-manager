<?php
return [
    'driver' => env('CUSTOM_QUEUE_DRIVER', 'eloquent'),
    'queue-pool-limit' => 2,
    'drivers' => [
        'eloquent' => \Nino\CustomQueueLaravel\Services\QueueManager\Eloquent::class,
        'redis' => \Nino\CustomQueueLaravel\Services\QueueManager\Redis::class,
    ],
    // if no rule, all classes queueable
    'whitelisted_classes' => [],
    'blacklisted_classes' => [],
    'task-class' => \Nino\CustomQueueLaravel\Models\CustomQueueTask::class
];
