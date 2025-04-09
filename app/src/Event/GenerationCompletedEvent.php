<?php

namespace App\Event;

use App\Interface\FizzBuzzRequestInterface;

class GenerationCompletedEvent extends BaseFizzBuzzEvent
{
    public const EVENT_NAME = 'fizzbuzz.generation.completed';

    public function __construct(
        FizzBuzzRequestInterface $request,
        array $context = []
    ) {
        parent::__construct($request, $context);
    }

    public function getEventName(): string
    {
        return self::EVENT_NAME;
    }
} 