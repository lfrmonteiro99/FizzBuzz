<?php

namespace App\Tests\Unit\Factory;

use App\Dto\FizzBuzzRequestDto;
use App\Factory\FizzBuzzRequestFactory;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\FizzBuzzRequestValidatorInterface;
use App\Request\FizzBuzzRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzRequestFactoryTest extends TestCase
{
    private FizzBuzzRequestFactory $factory;
    private FizzBuzzRequestValidatorInterface $validator;
    private ValidatorInterface $symfonyValidator;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(FizzBuzzRequestValidatorInterface::class);
        $this->symfonyValidator = $this->createMock(ValidatorInterface::class);
        
        $this->factory = new FizzBuzzRequestFactory(
            $this->validator,
            $this->symfonyValidator
        );
    }

    public function testCreateFromRequestWithValidParameters(): void
    {
        // Create a request with valid parameters
        $request = new Request([
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ]);
        
        // The symfony validator should not return any violations
        $this->symfonyValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        
        // The domain validator should not throw exceptions
        $this->validator->expects($this->once())
            ->method('validate');
        
        // Call the factory method
        $result = $this->factory->createFromRequest($request);
        
        // Assert we got a FizzBuzzRequest with the expected values
        $this->assertInstanceOf(FizzBuzzRequestInterface::class, $result);
        $this->assertEquals(3, $result->getDivisor1());
        $this->assertEquals(5, $result->getDivisor2());
        $this->assertEquals(15, $result->getLimit());
        $this->assertEquals('Fizz', $result->getStr1());
        $this->assertEquals('Buzz', $result->getStr2());
        $this->assertEquals(1, $result->getStart()); // Default start
    }

    public function testCreateFromRequestWithCustomStart(): void
    {
        // Create a Request that includes the start parameter
        $request = new Request([
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz',
            'start' => '5'
        ]);
        
        // The symfony validator should not return any violations
        $this->symfonyValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        
        // The domain validator should not throw exceptions
        $this->validator->expects($this->once())
            ->method('validate');
        
        // Call the factory method
        $result = $this->factory->createFromRequest($request);
        
        // Verify the request has all expected values
        $this->assertInstanceOf(FizzBuzzRequestInterface::class, $result);
        $this->assertEquals(3, $result->getDivisor1());
        $this->assertEquals(5, $result->getDivisor2());
        $this->assertEquals(15, $result->getLimit());
        $this->assertEquals('Fizz', $result->getStr1());
        $this->assertEquals('Buzz', $result->getStr2());
        $this->assertEquals(5, $result->getStart());
    }

    public function testCreateFromRequestWithDtoValidationErrors(): void
    {
        // Create a request with invalid parameters
        $request = new Request([
            'divisor1' => '0', // Invalid - must be positive
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ]);
        
        // Create a violation list
        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'The first divisor must be a positive number.',
                null,
                [],
                null,
                'divisor1',
                '0'
            )
        ]);
        
        // The symfony validator should return violations
        $this->symfonyValidator->expects($this->once())
            ->method('validate')
            ->willReturn($violations);
        
        // The domain validator should not be called because DTO validation fails
        $this->validator->expects($this->never())
            ->method('validate');
        
        // Call the factory method - should throw an exception
        $this->expectException(\InvalidArgumentException::class);
        $this->factory->createFromRequest($request);
    }

    public function testCreateFromRequestWithDomainValidationErrors(): void
    {
        // Create a request with parameters that pass DTO validation but fail domain validation
        $request = new Request([
            'divisor1' => '5',
            'divisor2' => '5', // Same as divisor1, which is invalid in domain
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ]);
        
        // The symfony validator should not return any violations
        $this->symfonyValidator->expects($this->once())
            ->method('validate')
            ->willReturn(new ConstraintViolationList());
        
        // The domain validator should throw an exception
        $this->validator->expects($this->once())
            ->method('validate')
            ->willThrowException(new \InvalidArgumentException('The divisors must be different.'));
        
        // Call the factory method - should throw the domain exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The divisors must be different.');
        $this->factory->createFromRequest($request);
    }
} 