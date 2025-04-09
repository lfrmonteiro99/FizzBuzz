<?php

namespace App\Tests\Unit\Service;

use App\Event\GenerationCompletedEvent;
use App\Interface\EventDispatcherInterface;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceLoggerInterface;
use App\Service\FizzBuzzEventService;
use PHPUnit\Framework\TestCase;

class FizzBuzzEventServiceTest extends TestCase
{
    private FizzBuzzEventService $service;
    private EventDispatcherInterface $eventDispatcher;
    private SequenceLoggerInterface $logger;
    
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(SequenceLoggerInterface::class);
        $this->service = new FizzBuzzEventService($this->eventDispatcher, $this->logger);
    }
    
    public function testDispatchEventWithEmptySequence(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(5);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');
        
        $sequence = [];
        
        // Assert that the logger methods are called
        $this->logger->expects($this->once())
            ->method('logSequenceStart')
            ->with([
                'start' => 1,
                'end' => 5,
                'first_divisor' => 3,
                'second_divisor' => 5,
                'first_word' => 'Fizz',
                'second_word' => 'Buzz'
            ]);
            
        $this->logger->expects($this->once())
            ->method('logSequenceComplete')
            ->with([
                'result_count' => 0
            ]);
            
        // Assert that dispatch is called with the correct event
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (GenerationCompletedEvent $event) use ($request) {
                $data = $event->getContext();
                return $event->getRequest() === $request
                    && $data['result_count'] === 0
                    && $data['first_item'] === null
                    && $data['last_item'] === null;
            }));
            
        // Act
        $this->service->dispatchEvent($request, $sequence);
    }
    
    public function testDispatchEventWithNonEmptySequence(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(5);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');
        
        $sequence = ['1', '2', 'Fizz', '4', 'Buzz'];
        
        // Assert that the logger methods are called
        $this->logger->expects($this->once())
            ->method('logSequenceStart')
            ->with([
                'start' => 1,
                'end' => 5,
                'first_divisor' => 3,
                'second_divisor' => 5,
                'first_word' => 'Fizz',
                'second_word' => 'Buzz'
            ]);
            
        $this->logger->expects($this->once())
            ->method('logSequenceComplete')
            ->with([
                'result_count' => 5
            ]);
            
        // Assert that dispatch is called with the correct event
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (GenerationCompletedEvent $event) use ($request) {
                $data = $event->getContext();
                return $event->getRequest() === $request
                    && $data['result_count'] === 5
                    && $data['first_item'] === '1'
                    && $data['last_item'] === 'Buzz';
            }));
            
        // Act
        $this->service->dispatchEvent($request, $sequence);
    }
    
    public function testDispatchEventWithCustomParameters(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(10);
        $request->method('getLimit')->willReturn(15);
        $request->method('getDivisor1')->willReturn(2);
        $request->method('getDivisor2')->willReturn(7);
        $request->method('getStr1')->willReturn('Even');
        $request->method('getStr2')->willReturn('Seven');
        
        $sequence = ['Even', '11', 'Even', '13', 'Even', 'Seven'];
        
        // Assert that the logger methods are called
        $this->logger->expects($this->once())
            ->method('logSequenceStart')
            ->with([
                'start' => 10,
                'end' => 15,
                'first_divisor' => 2,
                'second_divisor' => 7,
                'first_word' => 'Even',
                'second_word' => 'Seven'
            ]);
            
        $this->logger->expects($this->once())
            ->method('logSequenceComplete')
            ->with([
                'result_count' => 6
            ]);
            
        // Assert that dispatch is called with the correct event
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function (GenerationCompletedEvent $event) use ($request) {
                $data = $event->getContext();
                return $event->getRequest() === $request
                    && $data['result_count'] === 6
                    && $data['first_item'] === 'Even'
                    && $data['last_item'] === 'Seven';
            }));
            
        // Act
        $this->service->dispatchEvent($request, $sequence);
    }
} 