<?php

namespace App\Tests\Unit\Service;

use App\Event\ValidationFailedEvent;
use App\Interface\FizzBuzzRequestInterface;
use App\Request\FizzBuzzRequest;
use App\Service\FizzBuzzRequestValidator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzRequestValidatorTest extends TestCase
{
    private ValidatorInterface $validator;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private FizzBuzzRequestValidator $validatorService;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        
        // Set basic expectations for all tests
        $this->logger->expects($this->any())->method('info');
        $this->eventDispatcher->expects($this->any())->method('dispatch');
        
        $this->validatorService = new FizzBuzzRequestValidator(
            $this->validator,
            $this->logger,
            $this->eventDispatcher
        );
    }

    public function testValidRequestDoesNotThrowException(): void
    {
        // Create a valid request
        $request = $this->createValidRequest();
        
        // Act & Assert
        $this->validatorService->validate($request);
        $this->assertTrue(true); // If we get here, no exception was thrown
    }

    public function testNonPositiveStartValueThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithInvalidStart();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testNegativeDivisor1ThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithInvalidDivisor1();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testNegativeDivisor2ThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithInvalidDivisor2();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testDivisor1ExceedsMaximumThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithTooLargeDivisor1();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testDivisor2ExceedsMaximumThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithTooLargeDivisor2();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testIdenticalDivisorsThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithIdenticalDivisors();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testNonPositiveLimitThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithInvalidLimit();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testLimitExceedsMaximumThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithTooLargeLimit();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testStartExceedsLimitThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithStartExceedingLimit();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testEmptyStr1ThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithEmptyStr1();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testEmptyStr2ThrowsException(): void
    {
        // Create an invalid request
        $request = $this->createRequestWithEmptyStr2();
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testMultipleValidationErrorsAreLogged(): void
    {
        // Create a request with multiple validation errors
        $request = $this->createRequestWithMultipleErrors();
        
        // Expect logger to be called with multiple errors
        $this->logger->expects($this->once())
            ->method('info')
            ->with(
                'Validation failed',
                $this->callback(function ($context) {
                    return count($context['errors']) > 1;
                })
            );
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }

    public function testValidationFailedEventIsDispatched(): void
    {
        // Create a request with an error
        $request = $this->createRequestWithEmptyStr2();
        
        // Expect event dispatcher to be called
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(ValidationFailedEvent::class),
                ValidationFailedEvent::EVENT_NAME
            );
        
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->validatorService->validate($request);
    }
    
    // Helper methods to create test requests
    
    private function createValidRequest(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithInvalidStart(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 0; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithInvalidDivisor1(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return -1; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithInvalidDivisor2(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return -1; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithTooLargeDivisor1(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return 101; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithTooLargeDivisor2(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 101; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithIdenticalDivisors(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 3; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithInvalidLimit(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 0; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithTooLargeLimit(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 1001; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithStartExceedingLimit(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 10; }
            public function getLimit(): int { return 5; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithEmptyStr1(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return ''; }
            public function getStr2(): string { return 'buzz'; }
        };
    }
    
    private function createRequestWithEmptyStr2(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 1; }
            public function getLimit(): int { return 100; }
            public function getDivisor1(): int { return 3; }
            public function getDivisor2(): int { return 5; }
            public function getStr1(): string { return 'fizz'; }
            public function getStr2(): string { return ''; }
        };
    }
    
    private function createRequestWithMultipleErrors(): FizzBuzzRequestInterface
    {
        return new class implements FizzBuzzRequestInterface {
            public function getStart(): int { return 0; }
            public function getLimit(): int { return 0; }
            public function getDivisor1(): int { return -1; }
            public function getDivisor2(): int { return -1; }
            public function getStr1(): string { return ''; }
            public function getStr2(): string { return ''; }
        };
    }
} 