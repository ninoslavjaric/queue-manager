<?php

namespace Nino\CustomQueueLaravelTests\Unit\Services\QueueManager;

use Illuminate\Config\Repository;
use Nino\CustomQueueLaravel\Models\CustomQueueTask;
use Nino\CustomQueueLaravel\Services\Logger\DefaultLogger;
use Nino\CustomQueueLaravel\Services\Logger\ErrorLogger;
use Nino\CustomQueueLaravel\Services\QueueManager\Eloquent;
use Nino\CustomQueueLaravel\Services\QueueManagerPayloadException;
use Nino\CustomQueueLaravel\Services\QueueManagerPayloadValidator;
use PHPUnit\Framework\TestCase;
use Mockery;

class EloquentTest extends TestCase
{
    private Eloquent $queueManager;
    private ErrorLogger $errorLogger;
    private DefaultLogger $defaultLogger;
    private QueueManagerPayloadValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->errorLogger = $this->createMock(ErrorLogger::class);
        $this->defaultLogger = $this->createMock(DefaultLogger::class);
        $this->validator = $this->createMock(QueueManagerPayloadValidator::class);

        $this->queueManager = new Eloquent(
            errorLogger: $this->errorLogger,
            defaultLogger: $this->defaultLogger,
            validator: $this->validator,
        );

        $this->queueManager->setModel(Mockery::mock(CustomQueueTask::class));
    }


    public function test_it_cannot_append_a_task_on_bad_payload()
    {
        $this->validator->method('validate')->willThrowException(new QueueManagerPayloadException('abvgd'));
        $pushMock = $this->createPartialMock(Eloquent::class, ['push']);
        $cfgGetMock = $this->createPartialMock(Repository::class, ['get']);
        $pushMock->expects($this->never())->method('push');

        $task = new CustomQueueTask();

        $this->queueManager->append($task);

    }
}
