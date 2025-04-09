<?php

namespace App\Interface;

interface BaseLoggerInterface
{
    /**
     * Log a message with the given level and context.
     *
     * @param mixed $level The logging level
     * @param string $message The message to log
     * @param array $context The context data
     */
    public function log($level, string $message, array $context = []): void;
} 