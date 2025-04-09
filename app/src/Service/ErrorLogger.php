<?php

namespace App\Service;

use App\Formatter\CustomJsonFormatter;
use App\Interface\ErrorLoggerInterface;
use App\Message\LogMessage;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Messenger\MessageBusInterface;
use Stringable;
use Psr\Log\LoggerInterface;

class ErrorLogger implements ErrorLoggerInterface
{
    private const MAX_STRING_LENGTH = 128;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MessageBusInterface $messageBus
    ) {
    }

    public function logError(\Throwable $exception, array $context = []): void
    {
        $this->logger->error($exception->getMessage(), array_merge($context, [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString()
        ]));
    }

    public function logValidationError(string $message, array $errors, array $context = []): void
    {
        $this->logger->error($message, array_merge($context, [
            'validation_errors' => $errors
        ]));
    }

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->logger->emergency($message, $context);
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->logger->alert($message, $context);
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->logger->critical($message, $context);
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->logger->notice($message, $context);
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->logger->info($message, $context);
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->logger->debug($message, $context);
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->messageBus->dispatch(new LogMessage(
            $level,
            (string) $message,
            array_merge(['channel' => 'error'], $this->cleanContext($context))
        ));
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