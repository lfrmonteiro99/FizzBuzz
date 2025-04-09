<?php

namespace App\Service;

use App\Formatter\CustomJsonFormatter;
use App\Interface\RequestLoggerInterface;
use App\Message\LogMessage;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Messenger\MessageBusInterface;
use Stringable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class StructuredLogger extends Logger implements RequestLoggerInterface
{
    private const MAX_STRING_LENGTH = 128;

    public function __construct(
        string $name,
        string $logPath,
        private readonly MessageBusInterface $messageBus,
        array $handlers = [],
        array $processors = []
    ) {
        parent::__construct($name, $handlers, $processors);

        // Add custom processors
        foreach ($processors as $processor) {
            $this->pushProcessor($processor);
        }

        // Add stream handler with custom JSON formatter
        $streamHandler = new StreamHandler($logPath, Logger::DEBUG);
        $streamHandler->setFormatter(new CustomJsonFormatter());
        $this->pushHandler($streamHandler);
    }

    public function logRequest(Request $request, array $context = []): void
    {
        $this->info('Request received', array_merge([
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'query' => $request->query->all(),
            'headers' => $request->headers->all()
        ], $context));
    }

    public function logResponse(Response $response, array $context = []): void
    {
        $this->info('Response sent', array_merge([
            'status_code' => $response->getStatusCode(),
            'headers' => $response->headers->all()
        ], $context));
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->messageBus->dispatch(new LogMessage(
            $level,
            (string) $message,
            array_merge(['channel' => 'request'], $this->cleanContext($context))
        ));
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log(Logger::INFO, $message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log(Logger::ERROR, $message, $context);
    }

    /**
     * Log an error with exception details.
     *
     * @param \Throwable $exception The exception to log
     * @param array $context Additional context data
     */
    public function logError(\Throwable $exception, array $context = []): void
    {
        $this->error($exception->getMessage(), array_merge([
            'exception' => [
                'class' => get_class($exception),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]
        ], $context));
    }

    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $this->info(sprintf('Performance: %s took %.3fs', $operation, $duration), array_merge([
            'type' => 'performance'
        ], $context));
    }

    private function cleanContext(array $context): array
    {
        $cleaned = [];
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $cleaned[$key] = $this->cleanContext($value);
            } elseif (is_string($value)) {
                $cleaned[$key] = $this->truncateString($value);
            } else {
                $cleaned[$key] = $value;
            }
        }
        return $cleaned;
    }

    private function truncateString(string $value): string
    {
        if (strlen($value) > self::MAX_STRING_LENGTH) {
            return substr($value, 0, self::MAX_STRING_LENGTH) . '...';
        }
        return $value;
    }
} 