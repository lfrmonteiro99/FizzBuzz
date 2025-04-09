<?php

namespace App\Tests\Unit\Event;

use App\Event\ValidationFailedEvent;
use App\Interface\FizzBuzzRequestInterface;
use PHPUnit\Framework\TestCase;

class ValidationFailedEventTest extends TestCase
{
    private FizzBuzzRequestInterface $request;
    private array $errors;
    private ValidationFailedEvent $event;

    protected function setUp(): void
    {
        $this->request = $this->createMock(FizzBuzzRequestInterface::class);
        $this->errors = [
            ['field' => 'divisor1', 'message' => 'The first divisor must be a positive number.'],
            ['field' => 'str1', 'message' => 'The first string cannot be empty.']
        ];
        $this->event = new ValidationFailedEvent($this->request, $this->errors);
    }

    public function testGetEventName(): void
    {
        $this->assertEquals('fizzbuzz.validation.failed', $this->event->getEventName());
        $this->assertEquals(ValidationFailedEvent::EVENT_NAME, $this->event->getEventName());
    }

    public function testGetRequest(): void
    {
        $this->assertSame($this->request, $this->event->getRequest());
    }

    public function testGetErrors(): void
    {
        $this->assertSame($this->errors, $this->event->getErrors());
    }

    public function testGetContext(): void
    {
        $context = $this->event->getContext();
        $this->assertArrayHasKey('errors', $context);
        $this->assertSame($this->errors, $context['errors']);
    }

    public function testContextWithAdditionalData(): void
    {
        $additionalContext = ['test' => 'value'];
        $event = new ValidationFailedEvent($this->request, $this->errors, $additionalContext);
        
        $context = $event->getContext();
        $this->assertArrayHasKey('errors', $context);
        $this->assertArrayHasKey('test', $context);
        $this->assertSame($this->errors, $context['errors']);
        $this->assertEquals('value', $context['test']);
    }
} 