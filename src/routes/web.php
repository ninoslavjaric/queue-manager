<?php

use Illuminate\Support\Facades\Route;

Route::resource(
    'nino-queue-manager', \Nino\CustomQueueLaravel\Http\Controllers\QueueManagerController::class
)->parameter('nino-queue-manager', 'uuid')->except(['create', 'store', 'show', 'edit', 'update']);
