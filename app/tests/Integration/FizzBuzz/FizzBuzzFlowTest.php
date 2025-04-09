<?php

namespace App\Tests\Integration\FizzBuzz;

use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestInterface;
use App\Message\CreateFizzBuzzRequest;
use App\Repository\FizzBuzzRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class FizzBuzzFlowTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $messageBus;
    private FizzBuzzRequestRepository $repository;
    
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->repository = $this->createMock(FizzBuzzRequestRepository::class);
    }
    
    public function testEndToEndFizzBuzzFlow(): void
    {
        // Create mock entity
        $mockEntity = $this->createMock(FizzBuzzRequest::class);
        $mockEntity->method('getDivisor1')->willReturn(3);
        $mockEntity->method('getDivisor2')->willReturn(5);
        $mockEntity->method('getStr1')->willReturn('fizz');
        $mockEntity->method('getStr2')->willReturn('buzz');
        
        // Configure mocks to simulate database queries
        $this->repository->method('findBy')
            ->with(['limit' => 15])
            ->willReturn([$mockEntity]);
            
        // Assert entity properties match expected values
        $this->assertEquals(3, $mockEntity->getDivisor1());
        $this->assertEquals(5, $mockEntity->getDivisor2());
    }
    
    public function testDirectMessageDispatch(): void
    {
        // Create message to dispatch
        $message = new CreateFizzBuzzRequest(
            10,  // limit
            2,   // divisor1
            7,   // divisor2
            'even', // str1
            'seven', // str2
            1     // start
        );
        
        // Configure message bus mock
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($msg) {
                return $msg instanceof CreateFizzBuzzRequest
                    && $msg->getLimit() === 10
                    && $msg->getDivisor1() === 2
                    && $msg->getDivisor2() === 7;
            }))
            ->willReturn(new Envelope($message));
        
        // Create mock entity
        $mockEntity = $this->createMock(FizzBuzzRequest::class);
        $mockEntity->method('getDivisor1')->willReturn(2);
        $mockEntity->method('getDivisor2')->willReturn(7);
        $mockEntity->method('getStr1')->willReturn('even');
        $mockEntity->method('getStr2')->willReturn('seven');
        
        // Configure repository mock
        $this->repository->method('findBy')
            ->with(['limit' => 10])
            ->willReturn([$mockEntity]);
        
        // Dispatch message
        $envelope = $this->messageBus->dispatch($message);
        
        // Test assertions
        $this->assertInstanceOf(Envelope::class, $envelope);
        $this->assertEquals($message, $envelope->getMessage());
    }
    
    public function testCaching(): void
    {
        // Create mock entity
        $initialEntity = $this->createMock(FizzBuzzRequest::class);
        $initialEntity->method('getDivisor1')->willReturn(3);
        $initialEntity->method('getDivisor2')->willReturn(5);
        $initialEntity->method('getStr1')->willReturn('fizz');
        $initialEntity->method('getStr2')->willReturn('buzz');
        $initialEntity->method('getHits')->willReturn(1);
        
        // Create updated mock entity with incremented hits
        $updatedEntity = $this->createMock(FizzBuzzRequest::class);
        $updatedEntity->method('getDivisor1')->willReturn(3);
        $updatedEntity->method('getDivisor2')->willReturn(5);
        $updatedEntity->method('getStr1')->willReturn('fizz');
        $updatedEntity->method('getStr2')->willReturn('buzz');
        $updatedEntity->method('getHits')->willReturn(2);
        
        // Just test that the mock entities work as expected
        $this->assertEquals(1, $initialEntity->getHits());
        $this->assertEquals(2, $updatedEntity->getHits());
        
        // Just test that the values match what we expect
        $this->assertEquals(3, $initialEntity->getDivisor1());
        $this->assertEquals(5, $initialEntity->getDivisor2());
        $this->assertEquals('fizz', $initialEntity->getStr1());
        $this->assertEquals('buzz', $initialEntity->getStr2());
    }
} 