<?php

namespace App\Tests\Unit\Event;

use App\Event\GenerationCompletedEvent;
use App\Interface\FizzBuzzRequestInterface;
use PHPUnit\Framework\TestCase;

class GenerationCompletedEventTest extends TestCase
{
    private FizzBuzzRequestInterface $request;
    private array $context;
    private GenerationCompletedEvent $event;

    protected function setUp(): void
    {
        $this->request = $this->createMock(FizzBuzzRequestInterface::class);
        $this->context = ['sequence' => ['1', '2', 'fizz', '4', 'buzz']];
        $this->event = new GenerationCompletedEvent($this->request, $this->context);
    }

    public function testGetEventName(): void
    {
        $this->assertEquals('fizzbuzz.generation.completed', $this->event->getEventName());
        $this->assertEquals(GenerationCompletedEvent::EVENT_NAME, $this->event->getEventName());
    }

    public function testGetRequest(): void
    {
        $this->assertSame($this->request, $this->event->getRequest());
    }

    public function testGetContext(): void
    {
        $this->assertSame($this->context, $this->event->getContext());
    }

    public function testDefaultContext(): void
    {
        $eventWithDefaultContext = new GenerationCompletedEvent($this->request);
        $this->assertEmpty($eventWithDefaultContext->getContext());
    }
} 