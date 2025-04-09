<?php

namespace App\Interface;

interface FizzBuzzEventInterface
{
    /**
     * Get the FizzBuzz request associated with this event.
     *
     * @return FizzBuzzRequestInterface The FizzBuzz request
     */
    public function getRequest(): FizzBuzzRequestInterface;

    /**
     * Get the event context.
     *
     * @return array The event context
     */
    public function getContext(): array;

    /**
     * Get the event name.
     *
     * @return string The event name
     */
    public function getEventName(): string;
} 