<?php

return [
    'custom_queue_tasks' => [
        'driver' => 'single',
        'path' => storage_path('logs/background_jobs.log'),
    ],
    'custom_queue_tasks_errors' => [
        'driver' => 'single',
        'path' => storage_path('logs/background_jobs_errors.log'),
    ],
];
