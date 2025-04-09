<?php

namespace App\Interface;

use Symfony\Contracts\EventDispatcher\Event;

interface EventDispatcherInterface
{
    /**
     * Dispatch an event.
     *
     * @param Event $event The event to dispatch
     */
    public function dispatch(Event $event): void;
} 