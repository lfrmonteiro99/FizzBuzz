<?php

namespace App\Event;

use App\Entity\FizzBuzzRequest;
use Symfony\Contracts\EventDispatcher\Event;

class FizzBuzzEvent extends Event
{
    public const GENERATION_STARTED = 'fizzbuzz.generation.started';
    public const GENERATION_COMPLETED = 'fizzbuzz.generation.completed';
    public const INVALID_INPUT = 'fizzbuzz.invalid_input';
    public const ZERO_DIVISORS = 'fizzbuzz.zero_divisors';

    public function __construct(
        private readonly FizzBuzzRequest $request,
        private readonly array $context = [],
        private readonly string $eventName
    ) {
    }

    public function getRequest(): FizzBuzzRequest
    {
        return $this->request;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }
} 