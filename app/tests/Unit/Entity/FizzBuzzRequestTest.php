<?php

namespace App\Tests\Unit\Entity;

use App\Entity\FizzBuzzRequest;
use PHPUnit\Framework\TestCase;

class FizzBuzzRequestTest extends TestCase
{
    private FizzBuzzRequest $request;

    protected function setUp(): void
    {
        $this->request = new FizzBuzzRequest(
            100,
            3,
            5,
            'fizz',
            'buzz',
            1
        );
    }

    public function testConstructor(): void
    {
        // Basic constructor test
        $request = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz');
        
        $this->assertEquals(15, $request->getLimit());
        $this->assertEquals(3, $request->getDivisor1());
        $this->assertEquals(5, $request->getDivisor2());
        $this->assertEquals('fizz', $request->getStr1());
        $this->assertEquals('buzz', $request->getStr2());
        $this->assertEquals(1, $request->getStart()); // Default value
        $this->assertEquals(0, $request->getHits());
        $this->assertEquals(1, $request->getVersion());
        $this->assertEquals('pending', $request->getTrackingState());
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getUpdatedAt());
        $this->assertNull($request->getProcessedAt());
        
        // Constructor with custom start
        $request = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz', 10);
        $this->assertEquals(10, $request->getStart());
    }

    public function testFromArray(): void
    {
        // Complete data
        $request = FizzBuzzRequest::fromArray([
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => 10
        ]);
        
        $this->assertEquals(15, $request->getLimit());
        $this->assertEquals(3, $request->getDivisor1());
        $this->assertEquals(5, $request->getDivisor2());
        $this->assertEquals('fizz', $request->getStr1());
        $this->assertEquals('buzz', $request->getStr2());
        $this->assertEquals(10, $request->getStart());
        
        // Missing data (uses defaults)
        $request = FizzBuzzRequest::fromArray([
            'limit' => 15
        ]);
        
        $this->assertEquals(15, $request->getLimit());
        $this->assertEquals(0, $request->getDivisor1());
        $this->assertEquals(0, $request->getDivisor2());
        $this->assertEquals('', $request->getStr1());
        $this->assertEquals('', $request->getStr2());
        $this->assertEquals(1, $request->getStart());
        
        // Type conversion
        $request = FizzBuzzRequest::fromArray([
            'limit' => '15',
            'divisor1' => '3',
            'divisor2' => '5',
            'str1' => 123,
            'str2' => 456,
            'start' => '10'
        ]);
        
        $this->assertIsInt($request->getLimit());
        $this->assertIsInt($request->getDivisor1());
        $this->assertIsInt($request->getDivisor2());
        $this->assertIsString($request->getStr1());
        $this->assertIsString($request->getStr2());
        $this->assertIsInt($request->getStart());
    }

    public function testIncrementHits(): void
    {
        $this->assertEquals(0, $this->request->getHits());
        
        $originalUpdatedAt = $this->request->getUpdatedAt();
        $this->request->incrementHits();
        
        $this->assertEquals(1, $this->request->getHits());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $this->request->getUpdatedAt());
        
        $this->request->incrementHits();
        $this->assertEquals(2, $this->request->getHits());
    }

    public function testGetId(): void
    {
        $this->assertNull($this->request->getId());
    }

    public function testSetLimit(): void
    {
        $originalUpdatedAt = $this->request->getUpdatedAt();
        $result = $this->request->setLimit(200);
        
        $this->assertEquals(200, $this->request->getLimit());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $this->request->getUpdatedAt());
        $this->assertSame($this->request, $result);
    }

    public function testSetDivisor1(): void
    {
        $originalUpdatedAt = $this->request->getUpdatedAt();
        $result = $this->request->setDivisor1(7);
        
        $this->assertEquals(7, $this->request->getDivisor1());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $this->request->getUpdatedAt());
        $this->assertSame($this->request, $result);
    }

    public function testSetDivisor2(): void
    {
        $originalUpdatedAt = $this->request->getUpdatedAt();
        $result = $this->request->setDivisor2(11);
        
        $this->assertEquals(11, $this->request->getDivisor2());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $this->request->getUpdatedAt());
        $this->assertSame($this->request, $result);
    }

    public function testSetStr1(): void
    {
        $originalUpdatedAt = $this->request->getUpdatedAt();
        $result = $this->request->setStr1('foo');
        
        $this->assertEquals('foo', $this->request->getStr1());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $this->request->getUpdatedAt());
        $this->assertSame($this->request, $result);
    }

    public function testSetStr2(): void
    {
        $originalUpdatedAt = $this->request->getUpdatedAt();
        $result = $this->request->setStr2('bar');
        
        $this->assertEquals('bar', $this->request->getStr2());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $this->request->getUpdatedAt());
        $this->assertSame($this->request, $result);
    }

    public function testMarkAsProcessed(): void
    {
        $this->assertEquals('pending', $this->request->getTrackingState());
        $this->assertNull($this->request->getProcessedAt());
        
        $originalUpdatedAt = $this->request->getUpdatedAt();
        $this->request->markAsProcessed();
        
        $this->assertEquals('processed', $this->request->getTrackingState());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->request->getProcessedAt());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $this->request->getUpdatedAt());
    }

    public function testMarkAsFailed(): void
    {
        $this->assertEquals('pending', $this->request->getTrackingState());
        $this->assertNull($this->request->getProcessedAt());
        
        $originalUpdatedAt = $this->request->getUpdatedAt();
        $this->request->markAsFailed();
        
        $this->assertEquals('failed', $this->request->getTrackingState());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->request->getProcessedAt());
        $this->assertGreaterThanOrEqual($originalUpdatedAt, $this->request->getUpdatedAt());
    }

    public function testGettersReturnExpectedTypes(): void
    {
        $this->assertIsInt($this->request->getHits());
        $this->assertIsInt($this->request->getVersion());
        $this->assertIsString($this->request->getTrackingState());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->request->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $this->request->getUpdatedAt());
    }
} 