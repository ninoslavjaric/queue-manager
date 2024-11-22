<?php

use Illuminate\Support\Facades\Artisan;
use Nino\CustomQueueLaravel\Services\QueueManager;


Artisan::command('custom-queue:daemon', QueueManager::queueDaemonFunction())->everyTwoSeconds();
