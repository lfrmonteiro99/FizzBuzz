<?php

namespace App\EventSubscriber;

use App\Event\FizzBuzzEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FizzBuzzEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FizzBuzzEvent::GENERATION_STARTED => 'onGenerationStarted',
            FizzBuzzEvent::GENERATION_COMPLETED => 'onGenerationCompleted',
            FizzBuzzEvent::INVALID_INPUT => 'onInvalidInput',
            FizzBuzzEvent::ZERO_DIVISORS => 'onZeroDivisors',
        ];
    }

    public function onGenerationStarted(FizzBuzzEvent $event): void
    {
        $request = $event->getRequest();
        $this->logger->info('Starting FizzBuzz generation', [
            'limit' => $request->getLimit(),
            'int1' => $request->getInt1(),
            'int2' => $request->getInt2(),
            'str1' => $request->getStr1(),
            'str2' => $request->getStr2()
        ]);
    }

    public function onGenerationCompleted(FizzBuzzEvent $event): void
    {
        $context = $event->getContext();
        $this->logger->info('FizzBuzz generation completed', [
            'result_count' => $context['result_count'] ?? 0,
            'first_item' => $context['first_item'] ?? null,
            'last_item' => $context['last_item'] ?? null
        ]);
    }

    public function onInvalidInput(FizzBuzzEvent $event): void
    {
        $request = $event->getRequest();
        $this->logger->warning('Invalid limit provided', [
            'limit' => $request->getLimit()
        ]);
    }

    public function onZeroDivisors(FizzBuzzEvent $event): void
    {
        $context = $event->getContext();
        $this->logger->warning('Both divisors are zero', [
            'number' => $context['number'] ?? null
        ]);
    }
} 