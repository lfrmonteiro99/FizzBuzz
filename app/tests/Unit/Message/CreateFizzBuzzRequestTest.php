<?php

namespace App\Tests\Unit\Message;

use App\Message\CreateFizzBuzzRequest;
use PHPUnit\Framework\TestCase;

class CreateFizzBuzzRequestTest extends TestCase
{
    public function testCreateMessage(): void
    {
        $message = new CreateFizzBuzzRequest(
            15,  // limit
            3,   // divisor1
            5,   // divisor2
            'Fizz', // str1
            'Buzz', // str2
            1     // start
        );
        
        $this->assertEquals(15, $message->getLimit());
        $this->assertEquals(3, $message->getDivisor1());
        $this->assertEquals(5, $message->getDivisor2());
        $this->assertEquals('Fizz', $message->getStr1());
        $this->assertEquals('Buzz', $message->getStr2());
        $this->assertEquals(1, $message->getStart());
    }
    
    public function testCreateMessageWithDefaultStart(): void
    {
        $message = new CreateFizzBuzzRequest(
            15,  // limit
            3,   // divisor1
            5,   // divisor2
            'Fizz', // str1
            'Buzz'  // str2
            // start defaults to 1
        );
        
        $this->assertEquals(1, $message->getStart());
    }
    
    public function testToArray(): void
    {
        $message = new CreateFizzBuzzRequest(
            15,  // limit
            3,   // divisor1
            5,   // divisor2
            'Fizz', // str1
            'Buzz', // str2
            2     // start
        );
        
        $array = $message->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('limit', $array);
        $this->assertArrayHasKey('divisor1', $array);
        $this->assertArrayHasKey('divisor2', $array);
        $this->assertArrayHasKey('str1', $array);
        $this->assertArrayHasKey('str2', $array);
        $this->assertArrayHasKey('start', $array);
        
        $this->assertEquals(15, $array['limit']);
        $this->assertEquals(3, $array['divisor1']);
        $this->assertEquals(5, $array['divisor2']);
        $this->assertEquals('Fizz', $array['str1']);
        $this->assertEquals('Buzz', $array['str2']);
        $this->assertEquals(2, $array['start']);
    }
} 