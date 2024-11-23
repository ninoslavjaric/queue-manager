<?php

namespace Nino\CustomQueueLaravelTests\Unit\Services\QueueManager;

use Illuminate\Config\Repository;
use Illuminate\Support\Collection;
use Nino\CustomQueueLaravel\Models\CustomQueueTask;
use Nino\CustomQueueLaravel\Services\Logger\DefaultLogger;
use Nino\CustomQueueLaravel\Services\Logger\ErrorLogger;
use Nino\CustomQueueLaravel\Services\QueueManager;
use Nino\CustomQueueLaravel\Services\QueueManager\Eloquent;
use Nino\CustomQueueLaravel\Services\QueueManagerPayloadException;
use Nino\CustomQueueLaravel\Services\QueueManagerPayloadValidator;
use PHPUnit\Framework\TestCase;
use Mockery;
use function PHPUnit\Framework\exactly;

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

        $this->queueManager->setModel($this->createMock(CustomQueueTask::class));
    }


    public function testItRunBackgroundJobWithValidParamsExpectingFalseReflectionClassThrowsExceptionResult()
    {
        $reflectionClass = $this->createMock(\ReflectionClass::class);

        $partialMocks = $this->createPartialMock(Eloquent::class, ['formatMessage', 'getReflectionClassFor']);

        $partialMocks->method('getReflectionClassFor')->willReturn($reflectionClass);
        $reflectionClass->method('getConstructor')->willThrowException(new \ReflectionException('some wrong'));

        $partialMocks->setErrorLogger($this->errorLogger);
        $partialMocks->setDefaultLogger($this->defaultLogger);
        $partialMocks->setValidator($this->validator);

        $partialMocks->expects($this->once())->method('formatMessage')->willReturn('djes');
        $this->errorLogger->expects($this->once())->method('alert')->with('djes');
        $this->errorLogger->expects($this->never())->method('warning')->with('djes');
        $this->defaultLogger->expects($this->never())->method('info')->with('djes');

        $result = $partialMocks->runBackgroundJob('DynamicClass1', 'dynamicMethod', ['test'], retries: 2);

        $this->assertFalse($result);
    }

    public function testItRunBackgroundJobWithValidParamsExpectingFalseQueableClassThrowsExceptionResult()
    {
        $dynamicClass = <<<PHP
class DynamicClass1 {
    public function dynamicMethod(string \$message): void
    {
        throw new \Exception('hellog');
    }
}
PHP;
        eval($dynamicClass);

        $partialMocks = $this->createPartialMock(Eloquent::class, ['formatMessage']);
        $partialMocks->setErrorLogger($this->errorLogger);
        $partialMocks->setDefaultLogger($this->defaultLogger);
        $partialMocks->setValidator($this->validator);

        $partialMocks->expects($this->exactly(5))->method('formatMessage')->willReturn('djes');
        $this->errorLogger->expects($this->once())->method('alert')->with('djes');
        $this->errorLogger->expects($this->exactly(2))->method('warning')->with('djes');
        $this->defaultLogger->expects($this->exactly(2))->method('info')->with('djes');

        $result = $partialMocks->runBackgroundJob('DynamicClass1', 'dynamicMethod', ['test'], retries: 2);

        $this->assertFalse($result);
    }

    public function testItRunBackgroundJobWithValidParamsExpectingTrueResult()
    {
        $dynamicClass = <<<PHP
class DynamicClass2 {
    public function dynamicMethod(string \$message): void
    {

    }
}
PHP;
        eval($dynamicClass);

        $result = $this->queueManager->runBackgroundJob('DynamicClass2', 'dynamicMethod', ['test']);

        $this->assertTrue($result);
    }

    public function testItCannotAppendATaskOnBadPayload()
    {
        $this->validator->method('validate')->willThrowException(new QueueManagerPayloadException('abvgd'));
        $pushMock = $this->createPartialMock(Eloquent::class, ['push']);
        $cfgGetMock = $this->createPartialMock(Repository::class, ['get']);
        $pushMock->expects($this->never())->method('push');

        $task = new CustomQueueTask();

        $this->queueManager->append($task);

    }
}
