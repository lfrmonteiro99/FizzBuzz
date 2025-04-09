<?php

namespace App\Tests\Unit\Service;

use App\Interface\BaseLoggerInterface;
use App\Service\SequenceLogger;
use PHPUnit\Framework\TestCase;

class SequenceLoggerTest extends TestCase
{
    private SequenceLogger $logger;
    private BaseLoggerInterface $baseLogger;

    protected function setUp(): void
    {
        $this->baseLogger = $this->createMock(BaseLoggerInterface::class);
        $this->logger = new SequenceLogger($this->baseLogger);
    }

    public function testLog(): void
    {
        // Arrange
        $level = 'debug';
        $message = 'Test message';
        $context = ['key' => 'value'];
        
        // Expect base logger to be called with the same parameters
        $this->baseLogger->expects($this->once())
            ->method('log')
            ->with($level, $message, $context);
        
        // Act
        $this->logger->log($level, $message, $context);
        
        // Assert - done via expectations
    }
    
    public function testLogSequenceStart(): void
    {
        // Arrange
        $context = ['request_id' => '123'];
        
        // Expect base logger to be called with specific parameters
        $this->baseLogger->expects($this->once())
            ->method('log')
            ->with('info', 'Starting FizzBuzz sequence generation', $context);
        
        // Act
        $this->logger->logSequenceStart($context);
        
        // Assert - done via expectations
    }
    
    public function testLogSequenceStartWithDefaultContext(): void
    {
        // Expect base logger to be called with default empty context
        $this->baseLogger->expects($this->once())
            ->method('log')
            ->with('info', 'Starting FizzBuzz sequence generation', []);
        
        // Act
        $this->logger->logSequenceStart();
        
        // Assert - done via expectations
    }
    
    public function testLogSequenceComplete(): void
    {
        // Arrange
        $context = ['execution_time' => '0.5s'];
        
        // Expect base logger to be called with specific parameters
        $this->baseLogger->expects($this->once())
            ->method('log')
            ->with('info', 'FizzBuzz sequence generation completed', $context);
        
        // Act
        $this->logger->logSequenceComplete($context);
        
        // Assert - done via expectations
    }
    
    public function testLogSequenceCompleteWithDefaultContext(): void
    {
        // Expect base logger to be called with default empty context
        $this->baseLogger->expects($this->once())
            ->method('log')
            ->with('info', 'FizzBuzz sequence generation completed', []);
        
        // Act
        $this->logger->logSequenceComplete();
        
        // Assert - done via expectations
    }
} 