<?php

namespace App\MessageHandler;

use App\Event\FizzBuzzEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class FizzBuzzEventHandler
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(FizzBuzzEvent $event): void
    {
        $request = $event->getRequest();
        $context = $event->getContext();

        switch ($event->getEventName()) {
            case FizzBuzzEvent::GENERATION_STARTED:
                $this->logger->info('Starting FizzBuzz generation', [
                    'limit' => $request->getLimit(),
                    'int1' => $request->getInt1(),
                    'int2' => $request->getInt2(),
                    'str1' => $request->getStr1(),
                    'str2' => $request->getStr2()
                ]);
                break;

            case FizzBuzzEvent::GENERATION_COMPLETED:
                $this->logger->info('FizzBuzz generation completed', [
                    'result_count' => $context['result_count'] ?? 0,
                    'first_item' => $context['first_item'] ?? null,
                    'last_item' => $context['last_item'] ?? null
                ]);
                break;

            case FizzBuzzEvent::INVALID_INPUT:
                $this->logger->warning('Invalid limit provided', [
                    'limit' => $request->getLimit()
                ]);
                break;

            case FizzBuzzEvent::ZERO_DIVISORS:
                $this->logger->warning('Both divisors are zero', [
                    'number' => $context['number'] ?? null
                ]);
                break;
        }
    }
} 