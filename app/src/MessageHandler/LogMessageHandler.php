<?php

namespace App\MessageHandler;

use App\Interface\LogMessageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class LogMessageHandler
{
    public function __construct(
        private readonly LoggerInterface $requestLogger,
        private readonly LoggerInterface $errorLogger
    ) {
    }

    public function __invoke(LogMessageInterface $message): void
    {
        $context = $message->getContext();
        $channel = $context['channel'] ?? 'request';
        $logger = $channel === 'error' ? $this->errorLogger : $this->requestLogger;
        
        $logger->log(
            $message->getLevel(),
            $message->getMessage(),
            $context
        );
    }
} 