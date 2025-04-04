<?php

namespace App\Tests\Entity;

use App\Entity\FizzBuzzRequest;
use PHPUnit\Framework\TestCase;

class FizzBuzzRequestTest extends TestCase
{
    public function testConstructor(): void
    {
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        
        $this->assertEquals(15, $request->getLimit());
        $this->assertEquals(3, $request->getInt1());
        $this->assertEquals(5, $request->getInt2());
        $this->assertEquals('Fizz', $request->getStr1());
        $this->assertEquals('Buzz', $request->getStr2());
        $this->assertEquals(1, $request->getHits()); // Initial hits should be 1
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getUpdatedAt());
    }
    
    public function testFromArray(): void
    {
        $data = [
            'limit' => 15,
            'int1' => 3,
            'int2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ];
        
        $request = FizzBuzzRequest::fromArray($data);
        
        $this->assertEquals(15, $request->getLimit());
        $this->assertEquals(3, $request->getInt1());
        $this->assertEquals(5, $request->getInt2());
        $this->assertEquals('Fizz', $request->getStr1());
        $this->assertEquals('Buzz', $request->getStr2());
    }
    
    public function testFromArrayWithDefaultValues(): void
    {
        $data = [];
        
        $request = FizzBuzzRequest::fromArray($data);
        
        $this->assertEquals(0, $request->getLimit());
        $this->assertEquals(0, $request->getInt1());
        $this->assertEquals(0, $request->getInt2());
        $this->assertEquals('', $request->getStr1());
        $this->assertEquals('', $request->getStr2());
    }
    
    public function testIncrementHits(): void
    {
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $initialHits = $request->getHits();
        $initialUpdatedAt = $request->getUpdatedAt();
        
        // Sleep for a small amount to ensure timestamps are different
        usleep(1000);
        
        $request->incrementHits();
        
        $this->assertEquals($initialHits + 1, $request->getHits());
        $this->assertNotEquals($initialUpdatedAt, $request->getUpdatedAt());
    }
    
    public function testGetters(): void
    {
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        
        // Test all getters
        $this->assertNull($request->getId()); // ID should be null for a new entity
        $this->assertEquals(15, $request->getLimit());
        $this->assertEquals(3, $request->getInt1());
        $this->assertEquals(5, $request->getInt2());
        $this->assertEquals('Fizz', $request->getStr1());
        $this->assertEquals('Buzz', $request->getStr2());
        $this->assertEquals(1, $request->getHits());
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getUpdatedAt());
    }
} 