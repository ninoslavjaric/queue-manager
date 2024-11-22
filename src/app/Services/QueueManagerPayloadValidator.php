<?php

namespace Nino\CustomQueueLaravel\Services;

use Psr\Container\ContainerExceptionInterface;

class QueueManagerPayloadValidator
{
    /**
     * @param TaskI $task
     * @throws ContainerExceptionInterface
     * @throws QueueManagerPayloadException
     * @throws \ReflectionException
     */
    public function validate(TaskI $task): void
    {
        $className = $task->getClassName();

        if (!class_exists($className)) {
            throw new QueueManagerPayloadException("The class {$className} doesn't exist");
        }

        if (collect(config('queue-manager.blacklisted_classes'))->contains($className)) {
            throw new QueueManagerPayloadException("The class {$className} is blacklisted");
        }


        if (collect(config('queue-manager.whitelisted_classes'))->doesntContain($className)) {
            throw new QueueManagerPayloadException("The class {$className} is not whitelisted");
        }

        $method = $task->getMethod();

        $classMeta = new \ReflectionClass($className);

        collect($classMeta->getConstructor()?->getParameters())->each(
            fn(\ReflectionParameter $parameter) => app()->get($parameter->getType()->getName())
        );

        if (!$classMeta->hasMethod($method)) {
            throw new QueueManagerPayloadException("The class {$className} doesn't have method {$method}");
        }

        $methodMeta = $classMeta->getMethod($method);

        $params = collect($task->getParameters());

        if (
            $methodMeta->getNumberOfRequiredParameters() > $params->count()
            || $methodMeta->getNumberOfParameters() < $params->count()
        ) {
            throw new QueueManagerPayloadException(
                sprintf("Inconsistent number of params for %s::%s(%s)", $className, $method, $params->toJson())
            );
        }

        $gettypeParamHintsMap = collect([
            'int' => 'integer',
            'string' => 'string',
            'float' => 'double',
            'bool' => 'boolean',
            'array' => 'array',
        ]);

        $paramsMeta = collect($methodMeta->getParameters());

        $checkOfInconsistencyComparedMetaParamsWithTheParams = $paramsMeta->map(
            fn(\ReflectionParameter $paramMeta, int $key) => collect([$paramMeta->getType()->getName(), gettype($params->get($key))])
        )->filter(fn($arr) => $gettypeParamHintsMap->get($arr[0], null) != $arr[1])->isNotEmpty();

        if ($checkOfInconsistencyComparedMetaParamsWithTheParams) {
            throw new QueueManagerPayloadException(
                sprintf("Inconsistent types of params for %s::%s(%s)", $className, $method, $params->toJson())
            );
        }
    }
}
