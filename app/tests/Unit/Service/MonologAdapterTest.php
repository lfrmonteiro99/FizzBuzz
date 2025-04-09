<?php

namespace App\Tests\Unit\Service;

use App\Service\MonologAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MonologAdapterTest extends TestCase
{
    private LoggerInterface $logger;
    private MonologAdapter $adapter;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->adapter = new MonologAdapter($this->logger);
    }

    public function testLog(): void
    {
        // Arrange
        $level = 'info';
        $message = 'Test message';
        $context = ['test' => 'value'];
        
        // Assert that the underlying logger's log method is called with correct parameters
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($level),
                $this->equalTo($message),
                $this->equalTo($context)
            );
        
        // Act
        $this->adapter->log($level, $message, $context);
    }
    
    /**
     * @dataProvider logLevelProvider
     */
    public function testLogWithDifferentLevels(string $level): void
    {
        // Arrange
        $message = 'Test message';
        $context = ['test' => 'value'];
        
        // Assert
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($level),
                $this->equalTo($message),
                $this->equalTo($context)
            );
        
        // Act
        $this->adapter->log($level, $message, $context);
    }
    
    public function logLevelProvider(): array
    {
        return [
            ['emergency'],
            ['alert'],
            ['critical'],
            ['error'],
            ['warning'],
            ['notice'],
            ['info'],
            ['debug']
        ];
    }
    
    public function testLogWithEmptyContext(): void
    {
        // Arrange
        $level = 'info';
        $message = 'Test message';
        
        // Assert
        $this->logger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo($level),
                $this->equalTo($message),
                $this->equalTo([])
            );
        
        // Act
        $this->adapter->log($level, $message);
    }
} 