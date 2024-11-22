<?php

namespace Nino\CustomQueueLaravel\Services\Logger;

use Nino\CustomQueueLaravel\Services\Logger;

class DefaultLogger extends Logger
{
    protected string $channel = self::DEFAULT_CHANNEL;
}
