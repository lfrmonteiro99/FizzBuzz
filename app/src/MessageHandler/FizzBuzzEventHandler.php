<?php

namespace App\MessageHandler;

use App\Event\GenerationCompletedEvent;
use App\Interface\FizzBuzzEventInterface;
use App\Interface\RequestLoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FizzBuzzEventHandler
{
    public function __construct(
        private readonly RequestLoggerInterface $logger
    ) {
    }

    public function __invoke(FizzBuzzEventInterface $event): void
    {
        if ($event instanceof GenerationCompletedEvent) {
            $context = $event->getContext();
            $this->logger->info('FizzBuzz generation completed', [
                'result_count' => $context['result_count'] ?? 0,
                'first_item' => $context['first_item'] ?? null,
                'last_item' => $context['last_item'] ?? null
            ]);
        }
    }
} 