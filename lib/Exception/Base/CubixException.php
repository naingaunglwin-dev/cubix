<?php

namespace Cubix\Exception\Base;

trait CubixException
{
    /**
     * Log level
     *
     * @var string[]
     */
    protected array $levels = [
        E_ERROR             => 'ERROR',
        E_WARNING           => 'WARNING',
        E_PARSE             => 'ERROR',
        E_NOTICE            => 'INFO',
        E_CORE_ERROR        => 'ERROR',
        E_CORE_WARNING      => 'WARNING',
        E_COMPILE_ERROR     => 'ERROR',
        E_COMPILE_WARNING   => 'WARNING',
        E_USER_ERROR        => 'ERROR',
        E_USER_WARNING      => 'WARNING',
        E_USER_NOTICE       => 'INFO',
        E_RECOVERABLE_ERROR => 'ERROR',
        E_DEPRECATED        => 'WARNING',
        E_USER_DEPRECATED   => 'WARNING'
    ];

    /**
     * Get debug backtrace
     *
     * @param bool $asString
     * @return array|string
     */
    final public function backtrace(bool $asString = false): array|string
    {
        return $asString ? $this->getTraceAsString() : $this->getTrace();
    }

    /**
     * Get log level
     *
     * @return string
     */
    public function getLogLevel(): string
    {
        return $this->levels[$this->getCode()] ?? 'ERROR';
    }

    /**
     * Get Formatted log message
     *
     * @return string
     */
    public function getLogMessage(): string
    {
        return sprintf(
            '[%s] %s in %s on line %d',
            $this->getLogLevel(),
            $this->getMessage(),
            $this->getFile(),
            $this->getLine()
        );
    }
}
