<?php

namespace App\Tests\Unit\Dto;

use App\Dto\FizzBuzzRequestDto;
use App\Entity\FizzBuzzRequest;
use PHPUnit\Framework\TestCase;

class FizzBuzzRequestDtoTest extends TestCase
{
    public function testConstructWithDivisorNames(): void
    {
        $data = [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz',
            'start' => '1'
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        
        $this->assertEquals(3, $dto->getDivisor1());
        $this->assertEquals(5, $dto->getDivisor2());
        $this->assertEquals(15, $dto->getLimit());
        $this->assertEquals('Fizz', $dto->getStr1());
        $this->assertEquals('Buzz', $dto->getStr2());
        $this->assertEquals(1, $dto->getStart());
    }
    
    public function testConstructWithDefaultStart(): void
    {
        $data = [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
            // start defaults to 1
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        
        $this->assertEquals(1, $dto->getStart());
    }
    
    public function testConstructWithMissingParameters(): void
    {
        $data = [
            // Missing divisors, using defaults
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        
        $this->assertEquals(0, $dto->getDivisor1());
        $this->assertEquals(0, $dto->getDivisor2());
        $this->assertEquals(15, $dto->getLimit());
    }
    
    public function testFromRequest(): void
    {
        $data = [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ];
        
        $dto = FizzBuzzRequestDto::fromRequest($data);
        
        $this->assertInstanceOf(FizzBuzzRequestDto::class, $dto);
        $this->assertEquals(3, $dto->getDivisor1());
    }
    
    public function testToEntity(): void
    {
        $data = [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz',
            'start' => '2'
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        $entity = $dto->toEntity();
        
        $this->assertInstanceOf(FizzBuzzRequest::class, $entity);
        $this->assertEquals(3, $entity->getDivisor1());
        $this->assertEquals(5, $entity->getDivisor2());
        $this->assertEquals(15, $entity->getLimit());
        $this->assertEquals('Fizz', $entity->getStr1());
        $this->assertEquals('Buzz', $entity->getStr2());
        $this->assertEquals(2, $entity->getStart());
    }
    
    public function testFromEntity(): void
    {
        $entity = new FizzBuzzRequest(
            15,  // limit
            3,   // divisor1
            5,   // divisor2
            'Fizz', // str1
            'Buzz', // str2
            2     // start
        );
        
        $dto = FizzBuzzRequestDto::fromEntity($entity);
        
        $this->assertInstanceOf(FizzBuzzRequestDto::class, $dto);
        $this->assertEquals(3, $dto->getDivisor1());
        $this->assertEquals(5, $dto->getDivisor2());
        $this->assertEquals(15, $dto->getLimit());
        $this->assertEquals('Fizz', $dto->getStr1());
        $this->assertEquals('Buzz', $dto->getStr2());
        $this->assertEquals(2, $dto->getStart());
    }
    
    public function testToArray(): void
    {
        $data = [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'Fizz',
            'str2' => 'Buzz',
            'start' => '2'
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        $array = $dto->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(3, $array['divisor1']);
        $this->assertEquals(5, $array['divisor2']);
        $this->assertEquals(15, $array['limit']);
        $this->assertEquals('Fizz', $array['str1']);
        $this->assertEquals('Buzz', $array['str2']);
        $this->assertEquals(2, $array['start']);
    }
} 