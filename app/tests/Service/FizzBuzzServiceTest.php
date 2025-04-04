<?php

namespace App\Tests\Service;

use App\Entity\FizzBuzzRequest;
use App\Service\FizzBuzzService;
use PHPUnit\Framework\TestCase;

class FizzBuzzServiceTest extends TestCase
{
    private FizzBuzzService $service;

    protected function setUp(): void
    {
        $this->service = new FizzBuzzService();
    }

    public function testGenerateFizzBuzz(): void
    {
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $result = $this->service->generate($request);

        $this->assertEquals([
            '1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz',
            '11', 'Fizz', '13', '14', 'FizzBuzz'
        ], $result);
    }

    public function testGenerateWithDifferentParameters(): void
    {
        $request = new FizzBuzzRequest(6, 2, 3, 'Even', 'Three');
        $result = $this->service->generate($request);

        $this->assertEquals([
            '1', 'Even', 'Three', 'Even', '5', 'EvenThree'
        ], $result);
    }

    public function testGenerateWithSingleNumber(): void
    {
        $request = new FizzBuzzRequest(1, 2, 3, 'Fizz', 'Buzz');
        $result = $this->service->generate($request);

        $this->assertEquals(['1'], $result);
    }

    public function testGenerateWithSameDivisors(): void
    {
        $request = new FizzBuzzRequest(4, 2, 2, 'Even', 'Even');
        $result = $this->service->generate($request);

        $this->assertEquals([
            '1', 'EvenEven', '3', 'EvenEven'
        ], $result);
    }

    public function testGenerateWithZeroLimit(): void
    {
        $request = new FizzBuzzRequest(0, 2, 3, 'Fizz', 'Buzz');
        $result = $this->service->generate($request);

        $this->assertEquals([], $result);
    }

    public function testGenerateWithNegativeLimit(): void
    {
        $request = new FizzBuzzRequest(-5, 2, 3, 'Fizz', 'Buzz');
        $result = $this->service->generate($request);

        $this->assertEquals([], $result);
    }

    public function testGenerateWithZeroDivisors(): void
    {
        $request = new FizzBuzzRequest(5, 0, 0, 'Fizz', 'Buzz');
        $result = $this->service->generate($request);

        $this->assertEquals(['1', '2', '3', '4', '5'], $result);
    }

    public function testGenerateWithEmptyStrings(): void
    {
        $request = new FizzBuzzRequest(5, 2, 3, '', '');
        $result = $this->service->generate($request);

        $this->assertEquals(['1', '', '3', '', '5'], $result);
    }

    public function testGenerateWithLargeLimit(): void
    {
        $request = new FizzBuzzRequest(1000000, 2, 3, 'Even', 'Three');
        $result = $this->service->generate($request);

        $this->assertCount(1000000, $result);
        $this->assertEquals('EvenThree', $result[5]); // 6th element (index 5)
    }
} 