<?php

namespace Nino\CustomQueueLaravel\Services;

interface TaskI
{
    /**
     * @return string
     */
    public function getClassName(): string;

    /**
     * @return string
     */
    public function getMethod(): string;

    /**
     * @return array
     */
    public function getParameters(): array;

    /**
     * @return string
     */
    public function getPriority(): string;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @return int
     */
    public function getDelay(): int;

    /**
     * @return int
     */
    public function getRetries(): int;

    /**
     * @return string
     */
    public function __toString();

    /**
     * @return array
     */
    public function toArray();

    public function getUuid(): string;

    public function getPid(): int;
}
