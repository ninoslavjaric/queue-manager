[![Unit test execution](https://github.com/ninoslavjaric/queue-manager/actions/workflows/unittests.yaml/badge.svg)](https://github.com/ninoslavjaric/queue-manager/actions/workflows/unittests.yaml)
[![Create Release](https://github.com/ninoslavjaric/queue-manager/actions/workflows/release.yaml/badge.svg)](https://github.com/ninoslavjaric/queue-manager/actions/workflows/release.yaml)


# Custom Queue Manager for Laravel

## Overview

The Custom Queue Manager is a Laravel plugin designed to execute PHP classes as background jobs, independent of Laravel's built-in queue system. This system provides scalability, error handling, and ease of use while executing tasks asynchronously. It supports multiple queue storage drivers, starting with Eloquent and planning to extend to Redis.

---

## Installation

1. **Install the package** via Composer:

   ```bash
   composer require ninoslavjaric/queue-manager
   ```


2. **Run migrations** to set up the necessary database tables:

   ```bash
   php artisan migrate
   ```

5. **Add the scheduler** to your crontab to run the queue daemon:

   ```bash
   php artisan schedule:run
   ```

---

## Configuration

The configuration file for the QueueManager may be overriden and can be found in `config/custom_queue.php`. Here's an example of the configuration:

```php
return [
    'driver' => env('CUSTOM_QUEUE_DRIVER', 'eloquent'),
    'queue-pool-limit' => 2,
    'drivers' => [
        'eloquent' => \Nino\CustomQueueLaravel\Services\QueueManager\Eloquent::class,
        'redis' => \Nino\CustomQueueLaravel\Services\QueueManager\Redis::class,
    ],
    // Optional: Limit queueable classes
    'whitelisted_classes' => [],
    'blacklisted_classes' => [],
    'task-class' => \Nino\CustomQueueLaravel\Models\CustomQueueTask::class
];
```

- **`driver`**: Choose the queue storage driver (Eloquent, Redis).
- **`queue-pool-limit`**: Set the maximum number of concurrent tasks that can be processed. Default is 2.
- **`drivers`**: Define the drivers (currently Eloquent and Redis).
- **`whitelisted_classes` & `blacklisted_classes`**: Use these arrays to restrict which classes are allowed to be queued.

---

## Main Components

### QueueManager

The central class in this solution is `QueueManager`. It orchestrates all the actions in the queue system. It is an abstract class, and different storage backends extend it (e.g., `Eloquent`, `Redis`).

### Crucial Methods

1. **`queueDaemonFunction`**:
   - This function is scheduled to run every 2 seconds.
   - It retrieves idle tasks and creates asynchronous background jobs to execute specific tasks.
   
2. **`runBackgroundJob`**:
   - This function is executed by an asynchronous background job to process the task.
   
3. **`append`**:
   - This method pushes a task to the queue table, but only if it passes the validation.

4. **`push`**:
   - Adds a task to the queue table.

5. **`pop`**:
   - Fetches a list of tasks with the highest priority and oldest timestamps. You can specify the number of tasks to retrieve via the `limit` parameter.

---

## Logging

There are two types of logs:
- **Default logger**: Logs regular task execution.
- **Error logger**: Logs failed tasks or errors during task processing.

The log entries are structured as follows:

```txt
[2024-11-21 20:36:47] local.INFO: [nino/app/Services/QueueManager.php:219] {"flag":"custom-queue:daemon","queuePoolLimit":3} -----> Cancelling tasks that aren't running in system
```

- **Log format**: Includes context (e.g., task details) and the log level.
- **Log files**:
  - `background_jobs.log`: For regular logs.
  - `background_jobs_errors.log`: For error logs.

---

## Queue Daemon

The queue system runs a **daemon** that continuously monitors and executes background tasks. The daemon is triggered via the `custom-queue:daemon` command, and it processes tasks using the `php artisan custom-queue:task-bg-execute {uuid}` command.

### Example Log Entries

- Task preparation:
  ```txt
  [2024-11-21 20:36:47] local.INFO: [nino/app/Services/QueueManager.php:233] {"flag":"custom-queue:daemon","queuePoolLimit":3,"tasksExtractedCount":2} -----> Preparing tasks for execution
  ```
- Task execution:
  ```txt
  [2024-11-21 20:36:47] local.INFO: [nino/app/Services/QueueManager.php:246] {"flag":"custom-queue:daemon","queuePoolLimit":3,"tasksExtractedCount":2,"cmd":"'\/usr\/local\/bin\/php' '\/var\/www\/html\/artisan' 'custom-queue:task-bg-execute' '4919f8b9-06b9-4902-a96f-80c9bd7a8203'"} -----> Running task in background
  ```

---

## Web Interface

The plugin exposes a **web UI** for managing background tasks. You can list tasks, monitor their status, and cancel any running tasks. The web interface is available at `/nino-queue-manager`.

---

## Methods for Users

1. **`append`**:
   - Used programmatically to add a task to the queue.

2. **`cancelTask`**:
   - Exposed via URL to cancel a specific task.

3. **`getTasks`**:
   - Exposed via URL to retrieve a list of all tasks.

The QueueManager is registered as a singleton via a service provider and is injectable across your Laravel application.

---

## Scheduled Task

The queue daemon is scheduled using Laravel's scheduler:

```php
$schedule->command('custom-queue:daemon')->everyTwoSeconds();
```

Ensure the cron job for `php artisan schedule:run` is set up.

---

## Conclusion

This Custom Queue Manager provides a simple yet scalable solution for background task execution in Laravel, independent of the default queue system. It allows for easy management of tasks, logging, and web-based task management. By supporting multiple storage drivers and offering fine-grained control over task execution, it is flexible and highly customizable.


