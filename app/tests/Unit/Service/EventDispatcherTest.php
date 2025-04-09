<?php

namespace App\Tests\Unit\Service;

use App\Event\BaseFizzBuzzEvent;
use App\Interface\FizzBuzzRequestInterface;
use App\Service\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EventDispatcherTest extends TestCase
{
    private MessageBusInterface $messageBus;
    private EventDispatcher $eventDispatcher;

    protected function setUp(): void
    {
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->eventDispatcher = new EventDispatcher($this->messageBus);
    }

    public function testDispatch(): void
    {
        // Create a mock event
        $event = $this->createMock(Event::class);
        
        // Expect messageBus->dispatch to be called once with the event
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($event))
            ->willReturn(new Envelope(new \stdClass()));
        
        // Call the method under test
        $this->eventDispatcher->dispatch($event);
        
        // Test passes if messageBus->dispatch was called as expected
    }

    public function testDispatchWithFizzBuzzEvent(): void
    {
        // Create a concrete mock for BaseFizzBuzzEvent
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $event = $this->getMockForAbstractClass(
            BaseFizzBuzzEvent::class,
            [$request, ['test' => 'value']]
        );
        
        // Ensure the abstract method returns a value
        $event->method('getEventName')
            ->willReturn('test.event');
        
        // Expect messageBus->dispatch to be called once with the event
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->identicalTo($event))
            ->willReturn(new Envelope(new \stdClass()));
        
        // Call the method under test
        $this->eventDispatcher->dispatch($event);
        
        // Test passes if messageBus->dispatch was called as expected
    }
} 