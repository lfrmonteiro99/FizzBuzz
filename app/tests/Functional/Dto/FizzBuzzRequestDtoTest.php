<?php

namespace App\Tests\Functional\Dto;

use App\Dto\FizzBuzzRequestDto;
use App\Entity\FizzBuzzRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzRequestDtoTest extends KernelTestCase
{
    private ValidatorInterface $validator;
    
    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }
    
    public function testConstructor(): void
    {
        $data = [
            'limit' => '15',
            'divisor1' => '3',
            'divisor2' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '1'
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        
        $this->assertEquals(15, $dto->getLimit());
        $this->assertEquals(3, $dto->getDivisor1());
        $this->assertEquals(5, $dto->getDivisor2());
        $this->assertEquals('fizz', $dto->getStr1());
        $this->assertEquals('buzz', $dto->getStr2());
        $this->assertEquals(1, $dto->getStart());
    }
    
    public function testConstructorWithDefaults(): void
    {
        $data = [
            'limit' => '15',
            'divisor1' => '3',
            'divisor2' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            // No start parameter, should default to 1
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        
        $this->assertEquals(1, $dto->getStart());
    }
    
    public function testConstructorWithInvalidTypes(): void
    {
        $data = [
            'limit' => 'not-a-number',
            'divisor1' => 'three',
            'divisor2' => 'five',
            'str1' => null,
            'str2' => null,
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        
        // Values should be converted to their respective types, with numeric values becoming 0
        $this->assertEquals(0, $dto->getLimit());
        $this->assertEquals(0, $dto->getDivisor1());
        $this->assertEquals(0, $dto->getDivisor2());
        $this->assertEquals('', $dto->getStr1());
        $this->assertEquals('', $dto->getStr2());
        $this->assertEquals(1, $dto->getStart());
    }
    
    public function testFromRequest(): void
    {
        $data = [
            'limit' => '15',
            'divisor1' => '3',
            'divisor2' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '1'
        ];
        
        $dto = FizzBuzzRequestDto::fromRequest($data);
        
        $this->assertEquals(15, $dto->getLimit());
        $this->assertEquals(3, $dto->getDivisor1());
        $this->assertEquals(5, $dto->getDivisor2());
        $this->assertEquals('fizz', $dto->getStr1());
        $this->assertEquals('buzz', $dto->getStr2());
        $this->assertEquals(1, $dto->getStart());
    }
    
    public function testToEntity(): void
    {
        $data = [
            'limit' => '15',
            'divisor1' => '3',
            'divisor2' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '1'
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        $entity = $dto->toEntity();
        
        $this->assertInstanceOf(FizzBuzzRequest::class, $entity);
        $this->assertEquals(15, $entity->getLimit());
        $this->assertEquals(3, $entity->getDivisor1());
        $this->assertEquals(5, $entity->getDivisor2());
        $this->assertEquals('fizz', $entity->getStr1());
        $this->assertEquals('buzz', $entity->getStr2());
        $this->assertEquals(1, $entity->getStart());
    }
    
    public function testFromEntity(): void
    {
        $entity = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz', 1);
        $dto = FizzBuzzRequestDto::fromEntity($entity);
        
        $this->assertInstanceOf(FizzBuzzRequestDto::class, $dto);
        $this->assertEquals(15, $dto->getLimit());
        $this->assertEquals(3, $dto->getDivisor1());
        $this->assertEquals(5, $dto->getDivisor2());
        $this->assertEquals('fizz', $dto->getStr1());
        $this->assertEquals('buzz', $dto->getStr2());
        $this->assertEquals(1, $dto->getStart());
    }
    
    public function testToArray(): void
    {
        $data = [
            'limit' => '15',
            'divisor1' => '3',
            'divisor2' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '1'
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        $array = $dto->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(15, $array['limit']);
        $this->assertEquals(3, $array['divisor1']);
        $this->assertEquals(5, $array['divisor2']);
        $this->assertEquals('fizz', $array['str1']);
        $this->assertEquals('buzz', $array['str2']);
        $this->assertEquals(1, $array['start']);
    }
    
    public function testValidation(): void
    {
        $data = [
            'limit' => '15',
            'divisor1' => '3',
            'divisor2' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '1'
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        $violations = $this->validator->validate($dto);
        
        $this->assertCount(0, $violations);
    }
    
    public function testValidationFailure(): void
    {
        $data = [
            'limit' => '1001', // Too large
            'divisor1' => '0', // Not positive
            'divisor2' => '3',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '-1' // Not positive
        ];
        
        $dto = new FizzBuzzRequestDto($data);
        $violations = $this->validator->validate($dto);
        
        $this->assertGreaterThan(0, count($violations));
    }
} 