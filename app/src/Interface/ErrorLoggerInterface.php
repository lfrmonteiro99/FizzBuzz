<?php

namespace App\Interface;

use Psr\Log\LoggerInterface;

interface ErrorLoggerInterface extends LoggerInterface
{
    /**
     * Log an error with exception details.
     *
     * @param \Throwable $exception The exception to log
     * @param array $context Additional context to log
     */
    public function logError(\Throwable $exception, array $context = []): void;

    /**
     * Log a validation error.
     *
     * @param string $message The error message
     * @param array $errors The validation errors
     * @param array $context Additional context to log
     */
    public function logValidationError(string $message, array $errors, array $context = []): void;
} 