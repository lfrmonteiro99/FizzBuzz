<?php

namespace App\Interface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface RequestLoggerInterface extends BaseLoggerInterface
{
    /**
     * Log a request.
     *
     * @param Request $request The request to log
     * @param array $context Additional context to log
     */
    public function logRequest(Request $request, array $context = []): void;

    /**
     * Log a response.
     *
     * @param Response $response The response to log
     * @param array $context Additional context to log
     */
    public function logResponse(Response $response, array $context = []): void;
    
    /**
     * Log an error with exception details.
     *
     * @param \Throwable $exception The exception to log
     * @param array $context Additional context data
     */
    public function logError(\Throwable $exception, array $context = []): void;
} 