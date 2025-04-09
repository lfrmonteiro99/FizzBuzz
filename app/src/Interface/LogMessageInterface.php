<?php

namespace App\Interface;

interface LogMessageInterface
{
    /**
     * Get the log level.
     *
     * @return string The log level
     */
    public function getLevel(): string;

    /**
     * Get the log message.
     *
     * @return string The log message
     */
    public function getMessage(): string;

    /**
     * Get the log context.
     *
     * @return array The log context
     */
    public function getContext(): array;
} 