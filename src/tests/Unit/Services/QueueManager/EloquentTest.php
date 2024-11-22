<?php

namespace Nino\CustomQueueLaravelTests\Unit\Services\QueueManager;

use Nino\CustomQueueLaravel\Models\CustomQueueTask;
use Nino\CustomQueueLaravel\Services\Logger\DefaultLogger;
use Nino\CustomQueueLaravel\Services\Logger\ErrorLogger;
use Nino\CustomQueueLaravel\Services\QueueManager\Eloquent;
use Nino\CustomQueueLaravel\Services\QueueManagerPayloadValidator;
use PHPUnit\Framework\TestCase;
use Mockery;

class EloquentTest extends TestCase
{
    private Eloquent $queueManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueManager = new Eloquent(
            errorLogger: Mockery::mock(ErrorLogger::class),
            defaultLogger: Mockery::mock(DefaultLogger::class),
            validator: Mockery::mock(QueueManagerPayloadValidator::class),
        );

        $this->queueManager->setModel(Mockery::mock(CustomQueueTask::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
