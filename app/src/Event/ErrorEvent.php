<?php

namespace App\Event;

use App\Interface\FizzBuzzRequestInterface;

class ErrorEvent extends BaseFizzBuzzEvent
{
    public const EVENT_NAME = 'fizzbuzz.error';

    public function __construct(
        FizzBuzzRequestInterface $request,
        protected readonly \Throwable $exception,
        array $context = []
    ) {
        parent::__construct($request, array_merge($context, [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]));
    }

    public function getEventName(): string
    {
        return self::EVENT_NAME;
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }
} 