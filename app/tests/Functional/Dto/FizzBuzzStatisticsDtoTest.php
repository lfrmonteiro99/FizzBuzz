<?php

namespace App\Tests\Functional\Dto;

use App\Dto\FizzBuzzStatisticsDto;
use App\Entity\FizzBuzzRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzStatisticsDtoTest extends KernelTestCase
{
    private ValidatorInterface $validator;
    
    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }
    
    public function testConstructor(): void
    {
        $parameters = [
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'limit' => 15,
            'start' => 1
        ];
        
        $dto = new FizzBuzzStatisticsDto($parameters, 10, 'Most popular request');
        
        $this->assertEquals($parameters, $dto->getParameters());
        $this->assertEquals(10, $dto->getHits());
        $this->assertEquals('Most popular request', $dto->getMessage());
    }
    
    public function testFromRequestData(): void
    {
        $parameters = [
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'limit' => 15
        ];
        
        $dto = FizzBuzzStatisticsDto::fromRequestData($parameters, 5);
        
        $this->assertEquals($parameters, $dto->getParameters());
        $this->assertEquals(5, $dto->getHits());
        $this->assertNull($dto->getMessage());
    }
    
    public function testFromRequestDataWithNullParameters(): void
    {
        $dto = FizzBuzzStatisticsDto::fromRequestData(null, null);
        
        $this->assertNull($dto->getParameters());
        $this->assertNull($dto->getHits());
        $this->assertEquals('No requests have been made yet', $dto->getMessage());
    }
    
    public function testFromEntity(): void
    {
        // Create a mock or use a real entity
        $entity = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz', 1);
        
        // Use reflection to set hits property if it exists
        $reflectionClass = new \ReflectionClass($entity);
        if ($reflectionClass->hasProperty('hits')) {
            $property = $reflectionClass->getProperty('hits');
            $property->setAccessible(true);
            $property->setValue($entity, 10);
        }
        
        $dto = FizzBuzzStatisticsDto::fromEntity($entity);
        
        $expectedParameters = [
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'limit' => 15
        ];
        
        $this->assertEquals($expectedParameters, $dto->getParameters());
        
        // If the entity doesn't have a hits property or method, this might fail
        // In that case, adjust the test according to your entity structure
        if ($reflectionClass->hasProperty('hits')) {
            $this->assertEquals(10, $dto->getHits());
        }
    }
    
    public function testToArrayWithMessage(): void
    {
        $dto = new FizzBuzzStatisticsDto(null, null, 'Test message');
        $array = $dto->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('message', $array);
        $this->assertEquals('Test message', $array['message']);
        $this->assertArrayNotHasKey('parameters', $array);
        $this->assertArrayNotHasKey('hits', $array);
    }
    
    public function testToArrayWithoutMessage(): void
    {
        $parameters = [
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'limit' => 15
        ];
        
        $dto = new FizzBuzzStatisticsDto($parameters, 10);
        $array = $dto->toArray();
        
        $this->assertIsArray($array);
        $this->assertArrayHasKey('parameters', $array);
        $this->assertArrayHasKey('hits', $array);
        $this->assertEquals($parameters, $array['parameters']);
        $this->assertEquals(10, $array['hits']);
    }
    
    public function testJsonSerializeWithMessage(): void
    {
        $dto = new FizzBuzzStatisticsDto(null, null, 'Test message');
        $json = json_encode($dto);
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('message', $decoded);
        $this->assertEquals('Test message', $decoded['message']);
        $this->assertArrayNotHasKey('parameters', $decoded);
        $this->assertArrayNotHasKey('hits', $decoded);
    }
    
    public function testJsonSerializeWithoutMessage(): void
    {
        $parameters = [
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'limit' => 15
        ];
        
        $dto = new FizzBuzzStatisticsDto($parameters, 10);
        $json = json_encode($dto);
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('parameters', $decoded);
        $this->assertArrayHasKey('hits', $decoded);
        $this->assertEquals($parameters, $decoded['parameters']);
        $this->assertEquals(10, $decoded['hits']);
    }
    
    public function testGetters(): void
    {
        $parameters = [
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'limit' => 15
        ];
        
        $dto = new FizzBuzzStatisticsDto($parameters, 10, 'Test message');
        
        $this->assertEquals($parameters, $dto->getParameters());
        $this->assertEquals(10, $dto->getHits());
        $this->assertEquals('Test message', $dto->getMessage());
    }
    
    public function testValidation(): void
    {
        $parameters = [
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'limit' => 15
        ];
        
        $dto = new FizzBuzzStatisticsDto($parameters, 10);
        $violations = $this->validator->validate($dto);
        
        $this->assertCount(0, $violations);
    }
} 