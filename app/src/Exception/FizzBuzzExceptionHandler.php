<?php

namespace App\Exception;

use App\Interface\ErrorLoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class FizzBuzzExceptionHandler
{
    public function __construct(
        private readonly ErrorLoggerInterface $errorLogger
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        $this->errorLogger->logError($exception, [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'query' => $request->query->all()
        ]);

        $response = new JsonResponse([
            'error' => $this->getErrorMessage($exception),
            'details' => $this->getErrorDetails($exception)
        ], $this->getStatusCode($exception));

        $event->setResponse($response);
    }

    private function getStatusCode(\Throwable $exception): int
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        }

        return 500;
    }

    private function getErrorMessage(\Throwable $exception): string
    {
        if ($exception instanceof HttpExceptionInterface) {
            return $exception->getMessage();
        }

        return 'An unexpected error occurred';
    }

    private function getErrorDetails(\Throwable $exception): array
    {
        if ($exception instanceof FizzBuzzException) {
            return $exception->getDetails();
        }

        return [$exception->getMessage()];
    }
} 