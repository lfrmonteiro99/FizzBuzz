<?php

namespace App\Tests\Unit\MessageHandler;

use App\Message\CreateFizzBuzzRequest;
use App\MessageHandler\CreateFizzBuzzRequestHandler;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use App\Entity\FizzBuzzRequest;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CreateFizzBuzzRequestHandlerTest extends TestCase
{
    private CreateFizzBuzzRequestHandler $handler;
    private FizzBuzzRequestRepositoryInterface $repository;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(FizzBuzzRequestRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->handler = new CreateFizzBuzzRequestHandler(
            $this->repository,
            $this->logger
        );
    }

    public function testInvokeWithNewRequest(): void
    {
        // Create a message
        $message = new CreateFizzBuzzRequest(
            15,  // limit
            3,   // divisor1
            5,   // divisor2
            'Fizz', // str1
            'Buzz', // str2
            1     // start
        );

        // Create a mock entity
        $entity = $this->createMock(FizzBuzzRequest::class);
        $entity->method('getId')->willReturn(123);
        $entity->method('getHits')->willReturn(1);
        
        // Set up repository expectations
        $this->repository->expects($this->once())
            ->method('findOrCreateRequest')
            ->willReturn($entity);
            
        $this->repository->expects($this->once())
            ->method('incrementHits')
            ->with($entity);
            
        $this->repository->expects($this->once())
            ->method('markAsProcessed')
            ->with($entity);
        
        // Expect logging
        $this->logger->expects($this->exactly(2))
            ->method('info')
            ->withConsecutive(
                [$this->stringContains('Handling CreateFizzBuzzRequest message')],
                [$this->stringContains('Successfully processed FizzBuzz request')]
            );
        
        // Invoke the handler
        $this->handler->__invoke($message);
    }
    
    public function testInvokeHandlesErrors(): void
    {
        // Create a message
        $message = new CreateFizzBuzzRequest(
            15,  // limit
            3,   // divisor1
            5,   // divisor2
            'Fizz', // str1
            'Buzz', // str2
            1     // start
        );
        
        // Set up the repository mock to throw an exception
        $this->repository->expects($this->once())
            ->method('findOrCreateRequest')
            ->willThrowException(new \Exception('Test database exception'));
        
        // Expect error to be logged
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error creating FizzBuzz request'), $this->anything());
        
        // This should not rethrow the exception
        $this->handler->__invoke($message);
        
        // If we get here without exceptions, the test passes
        $this->assertTrue(true);
    }
} 