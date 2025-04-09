<?php

namespace App\Service;

use App\Interface\EventDispatcherInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EventDispatcher implements EventDispatcherInterface
{
    public function __construct(
        private readonly MessageBusInterface $messageBus
    ) {
    }

    /**
     * Dispatch an event.
     *
     * @param Event $event The event to dispatch
     */
    public function dispatch(Event $event): void
    {
        $this->messageBus->dispatch($event);
    }
} 