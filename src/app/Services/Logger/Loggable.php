<?php

namespace Nino\CustomQueueLaravel\Services\Logger;

trait Loggable
{

    private ErrorLogger $errorLogger;
    private DefaultLogger $defaultLogger;
    /**
     * @param ErrorLogger $errorLogger
     */
    public function setErrorLogger(ErrorLogger $errorLogger): void
    {
        $this->errorLogger = $errorLogger;
    }

    /**
     * @param DefaultLogger $defaultLogger
     */
    public function setDefaultLogger(DefaultLogger $defaultLogger): void
    {
        $this->defaultLogger = $defaultLogger;
    }

    /**
     * @param string $message
     * @param string $level
     * @return void
     */
    public function log(string $message, string $level = \Psr\Log\LogLevel::INFO): void
    {
        call_user_func_array([$this->defaultLogger, $level], [$message]);
    }

    /**
     * @param string $message
     * @param string $level
     * @return void
     */
    public function error_log(string $message, string $level = \Psr\Log\LogLevel::ALERT): void
    {
        call_user_func_array([$this->errorLogger, $level], [$message]);
    }

    public function formatMessage(array $context, string $message): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 1)[0];

        $relTrace = str_replace(base_path('/'), '', $trace['file']);
        return sprintf('[%s:%d] %s -----> %s', $relTrace, $trace['line'], json_encode($context), $message);
    }
}
