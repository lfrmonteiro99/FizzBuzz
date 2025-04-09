<?php

namespace App\Service;

use App\Interface\BaseLoggerInterface;
use App\Interface\SequenceLoggerInterface;

class SequenceLogger implements SequenceLoggerInterface
{
    public function __construct(
        private readonly BaseLoggerInterface $logger
    ) {
    }

    public function log($level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function logSequenceStart(array $context = []): void
    {
        $this->logger->log('info', 'Starting FizzBuzz sequence generation', $context);
    }

    public function logSequenceComplete(array $context = []): void
    {
        $this->logger->log('info', 'FizzBuzz sequence generation completed', $context);
    }
} 