<?php

namespace Nino\CustomQueueLaravel\Services\Logger;

use Nino\CustomQueueLaravel\Services\Logger;

class ErrorLogger extends Logger
{
    protected string $channel = self::ERROR_CHANNEL;
}
