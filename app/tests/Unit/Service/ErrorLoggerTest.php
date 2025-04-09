<?php

namespace App\Tests\Unit\Service;

use App\Message\LogMessage;
use App\Service\ErrorLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Throwable;

class ErrorLoggerTest extends TestCase
{
    private ErrorLogger $errorLogger;
    private LoggerInterface $logger;
    private MessageBusInterface $messageBus;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->errorLogger = new ErrorLogger($this->logger, $this->messageBus);
    }

    public function testLogError(): void
    {
        // Arrange
        $exception = new \Exception('Test exception');
        $context = ['test_key' => 'test_value'];
        
        // Expect logger to be called with enhanced context
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Test exception'),
                $this->callback(function($contextArg) use ($exception, $context) {
                    return isset($contextArg['exception']) 
                        && $contextArg['exception'] === $exception
                        && isset($contextArg['trace'])
                        && isset($contextArg['test_key'])
                        && $contextArg['test_key'] === 'test_value';
                })
            );
        
        // Act
        $this->errorLogger->logError($exception, $context);
    }
    
    public function testLogValidationError(): void
    {
        // Arrange
        $message = 'Validation failed';
        $errors = [
            ['field' => 'email', 'message' => 'Invalid email format']
        ];
        $context = ['request_id' => '123'];
        
        // Expect logger to be called with errors in context
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('Validation failed'),
                $this->callback(function($contextArg) use ($errors, $context) {
                    return isset($contextArg['validation_errors']) 
                        && $contextArg['validation_errors'] === $errors
                        && isset($contextArg['request_id'])
                        && $contextArg['request_id'] === '123';
                })
            );
        
        // Act
        $this->errorLogger->logValidationError($message, $errors, $context);
    }
    
    public function testEmergency(): void
    {
        // Arrange
        $message = 'Emergency message';
        $context = ['test' => 'value'];
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('emergency')
            ->with($message, $context);
        
        // Act
        $this->errorLogger->emergency($message, $context);
    }
    
    public function testAlert(): void
    {
        // Arrange
        $message = 'Alert message';
        $context = ['test' => 'value'];
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('alert')
            ->with($message, $context);
        
        // Act
        $this->errorLogger->alert($message, $context);
    }
    
    public function testCritical(): void
    {
        // Arrange
        $message = 'Critical message';
        $context = ['test' => 'value'];
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($message, $context);
        
        // Act
        $this->errorLogger->critical($message, $context);
    }
    
    public function testError(): void
    {
        // Arrange
        $message = 'Error message';
        $context = ['test' => 'value'];
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('error')
            ->with($message, $context);
        
        // Act
        $this->errorLogger->error($message, $context);
    }
    
    public function testWarning(): void
    {
        // Arrange
        $message = 'Warning message';
        $context = ['test' => 'value'];
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('warning')
            ->with($message, $context);
        
        // Act
        $this->errorLogger->warning($message, $context);
    }
    
    public function testNotice(): void
    {
        // Arrange
        $message = 'Notice message';
        $context = ['test' => 'value'];
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('notice')
            ->with($message, $context);
        
        // Act
        $this->errorLogger->notice($message, $context);
    }
    
    public function testInfo(): void
    {
        // Arrange
        $message = 'Info message';
        $context = ['test' => 'value'];
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('info')
            ->with($message, $context);
        
        // Act
        $this->errorLogger->info($message, $context);
    }
    
    public function testDebug(): void
    {
        // Arrange
        $message = 'Debug message';
        $context = ['test' => 'value'];
        
        // Expect logger to be called
        $this->logger->expects($this->once())
            ->method('debug')
            ->with($message, $context);
        
        // Act
        $this->errorLogger->debug($message, $context);
    }
    
    public function testLog(): void
    {
        // Arrange
        $level = 'info';
        $message = 'Log message';
        $context = ['test' => 'value'];
        
        // Expect message bus to be called with a LogMessage
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($logMessage) use ($level, $message, $context) {
                return $logMessage instanceof LogMessage
                    && $logMessage->getLevel() === $level
                    && $logMessage->getMessage() === $message
                    && isset($logMessage->getContext()['test'])
                    && $logMessage->getContext()['test'] === 'value'
                    && isset($logMessage->getContext()['channel'])
                    && $logMessage->getContext()['channel'] === 'error';
            }))
            ->willReturn(new Envelope(new LogMessage($level, $message, $context)));
        
        // Act
        $this->errorLogger->log($level, $message, $context);
    }
    
    public function testLogWithLongString(): void
    {
        // Arrange
        $level = 'debug';
        $message = 'Test message';
        $longString = str_repeat('a', 200); // String longer than MAX_STRING_LENGTH (128)
        $context = ['long_value' => $longString];
        
        // Expect message bus to be called with truncated string
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($logMessage) {
                $context = $logMessage->getContext();
                return isset($context['long_value'])
                    && strlen($context['long_value']) < 150 // Should be truncated
                    && strpos($context['long_value'], '...') !== false; // Should end with ...
            }))
            ->willReturn(new Envelope(new LogMessage($level, $message, $context)));
        
        // Act
        $this->errorLogger->log($level, $message, $context);
    }
    
    public function testLogWithNestedContext(): void
    {
        // Arrange
        $level = 'info';
        $message = 'Test message';
        $longString = str_repeat('a', 200);
        $context = [
            'nested' => [
                'long_value' => $longString,
                'normal_value' => 'test'
            ]
        ];
        
        // Expect message bus to be called with properly cleaned nested context
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($logMessage) {
                $context = $logMessage->getContext();
                return isset($context['nested']) 
                    && is_array($context['nested'])
                    && isset($context['nested']['long_value'])
                    && strpos($context['nested']['long_value'], '...') !== false
                    && isset($context['nested']['normal_value'])
                    && $context['nested']['normal_value'] === 'test';
            }))
            ->willReturn(new Envelope(new LogMessage($level, $message, $context)));
        
        // Act
        $this->errorLogger->log($level, $message, $context);
    }
    
    public function testLogWithStringable(): void
    {
        // Arrange
        $level = 'info';
        $stringable = new class implements \Stringable {
            public function __toString(): string {
                return 'Stringable object';
            }
        };
        $context = ['test' => 'value'];
        
        // Expect message bus to be called with string conversion
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($logMessage) {
                return $logMessage->getMessage() === 'Stringable object';
            }))
            ->willReturn(new Envelope(new LogMessage($level, 'Stringable object', $context)));
        
        // Act
        $this->errorLogger->log($level, $stringable, $context);
    }

    /**
     * Test string truncation through the log method with strings at boundary conditions.
     */
    public function testStringTruncationEdgeCases(): void
    {
        // Test cases with different string lengths to exercise all code paths in truncateString
        $testCases = [
            'short_string' => [
                'value' => 'Short string that will not be truncated',
                'expected_length' => 39, // original length
                'should_truncate' => false
            ],
            'exact_length' => [
                'value' => str_repeat('a', 128), // Exactly at the limit
                'expected_length' => 128, // original length
                'should_truncate' => false
            ],
            'just_over_limit' => [
                'value' => str_repeat('b', 129), // Just over the limit
                'expected_length' => 131, // 128 + 3 for '...'
                'should_truncate' => true
            ],
            'well_over_limit' => [
                'value' => str_repeat('c', 500), // Well over the limit
                'expected_length' => 131, // 128 + 3 for '...'
                'should_truncate' => true
            ]
        ];

        // Test each case separately
        foreach ($testCases as $case => $data) {
            // Arrange
            $context = ['test_value' => $data['value']];
            
            // Expect message bus to be called with properly processed string
            $this->messageBus->expects($this->once())
                ->method('dispatch')
                ->with($this->callback(function($logMessage) use ($data) {
                    $context = $logMessage->getContext();
                    $truncated = $context['test_value'];
                    
                    // Verify length
                    $expectedLength = $data['expected_length'];
                    $this->assertEquals($expectedLength, strlen($truncated));
                    
                    // Verify truncation indicators
                    if ($data['should_truncate']) {
                        $this->assertStringEndsWith('...', $truncated);
                    } else {
                        // If no truncation, string should be unchanged
                        $this->assertEquals($data['value'], $truncated);
                    }
                    
                    return true;
                }))
                ->willReturn(new Envelope(new LogMessage('info', 'test', [])));
            
            // Act
            $this->errorLogger->log('info', 'test', $context);
            
            // Reset mock expectations for next iteration
            $this->setUp();
        }
    }

    /**
     * Test the cleanContext method through the log method 
     * with various types of context values.
     */
    public function testCleanContextWithVariousTypes(): void
    {
        // Arrange
        $context = [
            'string_val' => 'Simple string',
            'int_val' => 123,
            'bool_val' => true,
            'null_val' => null,
            'array_val' => [1, 2, 3],
            'nested_array' => [
                'inner_string' => str_repeat('z', 200), // Long string in nested array
                'inner_int' => 456
            ]
        ];
        
        // Expect message bus to be called with properly cleaned context
        $this->messageBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(function($logMessage) {
                $context = $logMessage->getContext();
                
                // String values should be preserved if under limit
                $this->assertEquals('Simple string', $context['string_val']);
                
                // Non-string values should be passed through unchanged
                $this->assertEquals(123, $context['int_val']);
                $this->assertEquals(true, $context['bool_val']);
                $this->assertEquals(null, $context['null_val']);
                $this->assertEquals([1, 2, 3], $context['array_val']);
                
                // Nested long string should be truncated
                $this->assertStringEndsWith('...', $context['nested_array']['inner_string']);
                $this->assertEquals(456, $context['nested_array']['inner_int']);
                
                return true;
            }))
            ->willReturn(new Envelope(new LogMessage('info', 'test', [])));
        
        // Act
        $this->errorLogger->log('info', 'test', $context);
    }
} 