<?php

namespace App\Tests\Unit\Service;

use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceGeneratorInterface;
use App\Service\FizzBuzzSequenceService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class FizzBuzzSequenceServiceTest extends TestCase
{
    private FizzBuzzSequenceService $service;
    private SequenceGeneratorInterface $sequenceGenerator;

    protected function setUp(): void
    {
        $this->sequenceGenerator = $this->createMock(SequenceGeneratorInterface::class);
        $this->service = new FizzBuzzSequenceService($this->sequenceGenerator);
    }

    public function testGenerateSequence(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $expectedSequence = [
            '1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz',
            '11', 'Fizz', '13', '14', 'FizzBuzz'
        ];

        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(15);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');

        // Sequence generator expectations
        $this->sequenceGenerator->expects($this->once())
            ->method('generate')
            ->with($request)
            ->willReturn($expectedSequence);

        // Act
        $result = $this->service->generateSequence($request);

        // Assert
        $this->assertSame($expectedSequence, $result);
    }

    public function testGenerateSequenceWithCustomStart(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $expectedSequence = ['Buzz', 'Fizz', '7', '8', 'Fizz'];

        $request->method('getStart')->willReturn(5);
        $request->method('getLimit')->willReturn(5);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');

        // Sequence generator expectations
        $this->sequenceGenerator->expects($this->once())
            ->method('generate')
            ->with($request)
            ->willReturn($expectedSequence);

        // Act
        $result = $this->service->generateSequence($request);

        // Assert
        $this->assertSame($expectedSequence, $result);
    }

    public function testGenerateSequenceWithCustomStrings(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $expectedSequence = [
            '1', '2', 'Foo', '4', 'Bar', 'Foo', '7', '8', 'Foo', 'Bar',
            '11', 'Foo', '13', '14', 'FooBar'
        ];

        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(15);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('Foo');
        $request->method('getStr2')->willReturn('Bar');

        // Sequence generator expectations
        $this->sequenceGenerator->expects($this->once())
            ->method('generate')
            ->with($request)
            ->willReturn($expectedSequence);

        // Act
        $result = $this->service->generateSequence($request);

        // Assert
        $this->assertSame($expectedSequence, $result);
    }

    public function testGenerateSequenceWithCustomDivisors(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $expectedSequence = [
            '1', 'Fizz', '3', 'Fizz', '5', 'Fizz', '7', 'Fizz', '9', 'Fizz'
        ];

        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(10);
        $request->method('getDivisor1')->willReturn(2);
        $request->method('getDivisor2')->willReturn(7);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');

        // Sequence generator expectations
        $this->sequenceGenerator->expects($this->once())
            ->method('generate')
            ->with($request)
            ->willReturn($expectedSequence);

        // Act
        $result = $this->service->generateSequence($request);

        // Assert
        $this->assertSame($expectedSequence, $result);
    }

    public function testGenerateSequenceHandlesZeroDivisors(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $expectedSequence = ['1', '2', '3', '4', '5'];

        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(5);
        $request->method('getDivisor1')->willReturn(0);
        $request->method('getDivisor2')->willReturn(0);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');

        // Sequence generator expectations
        $this->sequenceGenerator->expects($this->once())
            ->method('generate')
            ->with($request)
            ->willReturn($expectedSequence);

        // Act
        $result = $this->service->generateSequence($request);

        // Assert
        $this->assertSame($expectedSequence, $result);
    }

    public function testGenerateSequenceHandlesEmptyStrings(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $expectedSequence = [
            '1', '2', '', '4', '', '', '7', '8', '', '',
            '11', '', '13', '14', ''
        ];

        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(15);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('');
        $request->method('getStr2')->willReturn('');

        // Sequence generator expectations
        $this->sequenceGenerator->expects($this->once())
            ->method('generate')
            ->with($request)
            ->willReturn($expectedSequence);

        // Act
        $result = $this->service->generateSequence($request);

        // Assert
        $this->assertSame($expectedSequence, $result);
    }

    public function testGenerateSequenceWithNegativeStart(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $expectedSequence = ['Buzz', 'Fizz', '-3', '-2', '-1'];

        $request->method('getStart')->willReturn(-5);
        $request->method('getLimit')->willReturn(5);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');

        // Sequence generator expectations
        $this->sequenceGenerator->expects($this->once())
            ->method('generate')
            ->with($request)
            ->willReturn($expectedSequence);

        // Act
        $result = $this->service->generateSequence($request);

        // Assert
        $this->assertSame($expectedSequence, $result);
    }

    public function testGenerateSequenceWithZeroLimit(): void
    {
        // Arrange
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $expectedSequence = [];

        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(0);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');

        // Sequence generator expectations
        $this->sequenceGenerator->expects($this->once())
            ->method('generate')
            ->with($request)
            ->willReturn($expectedSequence);

        // Act
        $result = $this->service->generateSequence($request);

        // Assert
        $this->assertSame($expectedSequence, $result);
    }
} 