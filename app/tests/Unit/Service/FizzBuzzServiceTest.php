<?php

namespace App\Tests\Unit\Service;

use App\Interface\FizzBuzzEventServiceInterface;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\FizzBuzzSequenceServiceInterface;
use App\Interface\SequenceCacheInterface;
use App\Message\CreateFizzBuzzRequest;
use App\Service\FizzBuzzService;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class FizzBuzzServiceTest extends TestCase
{
    private FizzBuzzService $service;
    private FizzBuzzSequenceServiceInterface $sequenceService;
    private FizzBuzzEventServiceInterface $eventService;
    private SequenceCacheInterface $cache;
    private MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private Connection $connection;
    private FizzBuzzRequestInterface $request;

    protected function setUp(): void
    {
        $this->sequenceService = $this->createMock(FizzBuzzSequenceServiceInterface::class);
        $this->eventService = $this->createMock(FizzBuzzEventServiceInterface::class);
        $this->cache = $this->createMock(SequenceCacheInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->request = $this->createMock(FizzBuzzRequestInterface::class);

        $this->service = new FizzBuzzService(
            $this->sequenceService,
            $this->eventService,
            $this->cache,
            $this->messageBus,
            $this->logger,
            $this->connection
        );
        
        // Configure request mock with default values
        $this->request->method('getStart')->willReturn(1);
        $this->request->method('getLimit')->willReturn(15);
        $this->request->method('getDivisor1')->willReturn(3);
        $this->request->method('getDivisor2')->willReturn(5);
        $this->request->method('getStr1')->willReturn('fizz');
        $this->request->method('getStr2')->willReturn('buzz');
    }

    public function testGenerateSequenceCacheHit(): void
    {
        // Expected sequence for a cache hit
        $expectedSequence = ['1', '2', 'fizz', '4', 'buzz'];
        
        // Configure cache to return a result (cache hit)
        $this->cache->method('get')
            ->with($this->request)
            ->willReturn($expectedSequence);
        
        // Configure messageBus to return an envelope
        $this->messageBus->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));
        
        // Logger should log info about cache hit
        $this->logger->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                [$this->equalTo('Generating sequence'), $this->anything()],
                [$this->equalTo('Found sequence in cache')],
                [$this->equalTo('Message dispatched for async processing')]
            );
            
        // Sequence service should not be called (cache hit)
        $this->sequenceService->expects($this->never())
            ->method('generateSequence');
            
        // Event service should not be called (cache hit)
        $this->eventService->expects($this->never())
            ->method('dispatchEvent');
        
        // Call the method under test
        $result = $this->service->generateSequence($this->request);
        
        // Assert we get the expected sequence from cache
        $this->assertEquals($expectedSequence, $result);
    }
    
    public function testGenerateSequenceCacheMiss(): void
    {
        // Expected sequence for a cache miss
        $expectedSequence = ['1', '2', 'fizz', '4', 'buzz', 'fizz', '7', '8', 'fizz', 'buzz'];
        
        // Configure cache to return null (cache miss)
        $this->cache->method('get')
            ->with($this->request)
            ->willReturn(null);
            
        // Configure sequence service to generate a sequence
        $this->sequenceService->method('generateSequence')
            ->with($this->request)
            ->willReturn($expectedSequence);
            
        // Configure messageBus to return an envelope
        $this->messageBus->method('dispatch')
            ->willReturn(new Envelope(new \stdClass()));
        
        // Cache should be called to set the generated sequence
        $this->cache->expects($this->once())
            ->method('set')
            ->with($this->request, $expectedSequence);
            
        // Event service should be called to dispatch an event
        $this->eventService->expects($this->once())
            ->method('dispatchEvent')
            ->with($this->request, $expectedSequence);
            
        // Logger should log various steps
        $this->logger->expects($this->atLeastOnce())
            ->method('info');
            
        // Call the method under test
        $result = $this->service->generateSequence($this->request);
        
        // Assert we get the expected sequence from the sequence service
        $this->assertEquals($expectedSequence, $result);
    }
    
    public function testGenerateSequenceWithException(): void
    {
        // Configure cache to return null (cache miss)
        $this->cache->method('get')
            ->with($this->request)
            ->willReturn(null);
            
        // Configure sequence service to throw an exception
        $exception = new \RuntimeException('Test exception');
        $this->sequenceService->method('generateSequence')
            ->willThrowException($exception);
            
        // Logger should log the error
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error generating sequence', $this->anything());
            
        // Cache should not be called to set any sequence
        $this->cache->expects($this->never())
            ->method('set');
            
        // Event service should not be called to dispatch any event
        $this->eventService->expects($this->never())
            ->method('dispatchEvent');
            
        // Message bus should not be called to dispatch any message
        $this->messageBus->expects($this->never())
            ->method('dispatch');
            
        // Call the method under test and expect an exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test exception');
        
        $this->service->generateSequence($this->request);
    }
    
    public function testMessengerDispatchFailure(): void
    {
        // Expected sequence
        $expectedSequence = ['1', '2', 'fizz', '4', 'buzz'];
        
        // Configure sequence service to generate a sequence
        $this->sequenceService->method('generateSequence')
            ->with($this->request)
            ->willReturn($expectedSequence);
            
        // Configure cache to return null (cache miss)
        $this->cache->method('get')
            ->with($this->request)
            ->willReturn(null);
            
        // Configure messageBus to throw an exception
        $this->messageBus->method('dispatch')
            ->willThrowException(new \RuntimeException('Messenger error'));
            
        // Logger should log the error with messenger
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error dispatching message', $this->anything());
            
        // Call the method under test - should not throw the messenger exception
        $result = $this->service->generateSequence($this->request);
        
        // Assert we still get the sequence despite the messenger error
        $this->assertEquals($expectedSequence, $result);
    }
    
    public function testDispatchMessageMethodCreatesCorrectMessage(): void
    {
        // Expected sequence
        $expectedSequence = ['1', '2', 'fizz', '4', 'buzz'];
        
        // Configure sequence service to generate a sequence
        $this->sequenceService->method('generateSequence')
            ->with($this->request)
            ->willReturn($expectedSequence);
            
        // Configure cache to return null (cache miss)
        $this->cache->method('get')
            ->with($this->request)
            ->willReturn(null);
            
        // Capture the message that is dispatched
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function ($message) {
                $this->assertInstanceOf(CreateFizzBuzzRequest::class, $message);
                $this->assertEquals(15, $message->getLimit());
                $this->assertEquals(3, $message->getDivisor1());
                $this->assertEquals(5, $message->getDivisor2());
                $this->assertEquals('fizz', $message->getStr1());
                $this->assertEquals('buzz', $message->getStr2());
                $this->assertEquals(1, $message->getStart());
                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));
            
        // Call the method under test
        $this->service->generateSequence($this->request);
    }
} 