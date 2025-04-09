<?php

namespace App\Service;

use App\Event\GenerationCompletedEvent;
use App\Interface\EventDispatcherInterface;
use App\Interface\FizzBuzzEventServiceInterface;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceLoggerInterface;

class FizzBuzzEventService implements FizzBuzzEventServiceInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly SequenceLoggerInterface $logger
    ) {
    }

    /**
     * Dispatch a FizzBuzz event.
     *
     * @param FizzBuzzRequestInterface $request The FizzBuzz request
     * @param array $sequence The generated sequence
     */
    public function dispatchEvent(FizzBuzzRequestInterface $request, array $sequence): void
    {
        $this->logger->logSequenceStart([
            'start' => $request->getStart(),
            'end' => $request->getLimit(),
            'first_divisor' => $request->getDivisor1(),
            'second_divisor' => $request->getDivisor2(),
            'first_word' => $request->getStr1(),
            'second_word' => $request->getStr2()
        ]);

        $this->eventDispatcher->dispatch(new GenerationCompletedEvent(
            $request,
            [
                'result_count' => count($sequence),
                'first_item' => $sequence[0] ?? null,
                'last_item' => $sequence[count($sequence) - 1] ?? null
            ]
        ));

        $this->logger->logSequenceComplete([
            'result_count' => count($sequence)
        ]);
    }
} 