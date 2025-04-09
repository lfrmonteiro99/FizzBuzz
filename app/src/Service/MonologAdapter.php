<?php

namespace App\Service;

use App\Interface\BaseLoggerInterface;
use Psr\Log\LoggerInterface;

class MonologAdapter implements BaseLoggerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function log($level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }
} 