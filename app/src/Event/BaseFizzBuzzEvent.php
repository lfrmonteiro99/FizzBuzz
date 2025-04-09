<?php

namespace App\Event;

use App\Interface\FizzBuzzEventInterface;
use App\Interface\FizzBuzzRequestInterface;
use Symfony\Contracts\EventDispatcher\Event;

abstract class BaseFizzBuzzEvent extends Event implements FizzBuzzEventInterface
{
    public function __construct(
        protected readonly FizzBuzzRequestInterface $request,
        protected readonly array $context = []
    ) {
    }

    public function getRequest(): FizzBuzzRequestInterface
    {
        return $this->request;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    abstract public function getEventName(): string;
} 