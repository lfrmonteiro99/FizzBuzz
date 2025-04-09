<?php

namespace App\Tests\Unit\Dto;

use App\Dto\FizzBuzzStatisticsDto;
use App\Entity\FizzBuzzRequest;
use PHPUnit\Framework\TestCase;

class FizzBuzzStatisticsDtoTest extends TestCase
{
    public function testConstructor(): void
    {
        // Arrange & Act
        $parameters = ['divisor1' => 3, 'divisor2' => 5, 'limit' => 15, 'str1' => 'Fizz', 'str2' => 'Buzz'];
        $hits = 42;
        $dto = new FizzBuzzStatisticsDto($parameters, $hits);
        
        // Assert
        $this->assertSame($parameters, $dto->getParameters());
        $this->assertSame($hits, $dto->getHits());
        $this->assertNull($dto->getMessage());
    }
    
    public function testConstructorWithMessage(): void
    {
        // Arrange & Act
        $parameters = ['divisor1' => 3, 'divisor2' => 5, 'limit' => 15, 'str1' => 'Fizz', 'str2' => 'Buzz'];
        $hits = 42;
        $message = 'Test message';
        $dto = new FizzBuzzStatisticsDto($parameters, $hits, $message);
        
        // Assert
        $this->assertSame($parameters, $dto->getParameters());
        $this->assertSame($hits, $dto->getHits());
        $this->assertSame($message, $dto->getMessage());
    }
    
    public function testFromRequestDataWithValidData(): void
    {
        // Arrange
        $parameters = ['divisor1' => 3, 'divisor2' => 5, 'limit' => 15, 'str1' => 'Fizz', 'str2' => 'Buzz'];
        $hits = 42;
        
        // Act
        $dto = FizzBuzzStatisticsDto::fromRequestData($parameters, $hits);
        
        // Assert
        $this->assertSame($parameters, $dto->getParameters());
        $this->assertSame($hits, $dto->getHits());
        $this->assertNull($dto->getMessage());
    }
    
    public function testFromRequestDataWithNullParameters(): void
    {
        // Arrange & Act
        $dto = FizzBuzzStatisticsDto::fromRequestData(null, 42);
        
        // Assert
        $this->assertNull($dto->getParameters());
        $this->assertNull($dto->getHits());
        $this->assertSame('No requests have been made yet', $dto->getMessage());
    }
    
    public function testFromRequestDataWithNullHits(): void
    {
        // Arrange
        $parameters = ['divisor1' => 3, 'divisor2' => 5, 'limit' => 15, 'str1' => 'Fizz', 'str2' => 'Buzz'];
        
        // Act
        $dto = FizzBuzzStatisticsDto::fromRequestData($parameters, null);
        
        // Assert
        $this->assertNull($dto->getParameters());
        $this->assertNull($dto->getHits());
        $this->assertSame('No requests have been made yet', $dto->getMessage());
    }
    
    public function testFromEntity(): void
    {
        // Arrange
        $entity = $this->createMock(FizzBuzzRequest::class);
        $entity->method('getLimit')->willReturn(15);
        $entity->method('getDivisor1')->willReturn(3);
        $entity->method('getDivisor2')->willReturn(5);
        $entity->method('getStr1')->willReturn('Fizz');
        $entity->method('getStr2')->willReturn('Buzz');
        $entity->method('getHits')->willReturn(42);
        
        // Act
        $dto = FizzBuzzStatisticsDto::fromEntity($entity);
        
        // Assert
        $expectedParameters = [
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz',
        ];
        $this->assertEquals($expectedParameters, $dto->getParameters());
        $this->assertSame(42, $dto->getHits());
        $this->assertNull($dto->getMessage());
    }
    
    public function testToArrayWithMessage(): void
    {
        // Arrange
        $dto = new FizzBuzzStatisticsDto(null, null, 'Test message');
        
        // Act
        $result = $dto->toArray();
        
        // Assert
        $expected = ['message' => 'Test message'];
        $this->assertEquals($expected, $result);
    }
    
    public function testToArrayWithParameters(): void
    {
        // Arrange
        $parameters = ['divisor1' => 3, 'divisor2' => 5, 'limit' => 15, 'str1' => 'Fizz', 'str2' => 'Buzz'];
        $hits = 42;
        $dto = new FizzBuzzStatisticsDto($parameters, $hits);
        
        // Act
        $result = $dto->toArray();
        
        // Assert
        $expected = [
            'parameters' => $parameters,
            'hits' => $hits
        ];
        $this->assertEquals($expected, $result);
    }
    
    public function testJsonSerializeWithMessage(): void
    {
        // Arrange
        $dto = new FizzBuzzStatisticsDto(null, null, 'Test message');
        
        // Act
        $result = $dto->jsonSerialize();
        
        // Assert
        $expected = ['message' => 'Test message'];
        $this->assertEquals($expected, $result);
    }
    
    public function testJsonSerializeWithParameters(): void
    {
        // Arrange
        $parameters = ['divisor1' => 3, 'divisor2' => 5, 'limit' => 15, 'str1' => 'Fizz', 'str2' => 'Buzz'];
        $hits = 42;
        $dto = new FizzBuzzStatisticsDto($parameters, $hits);
        
        // Act
        $result = $dto->jsonSerialize();
        
        // Assert
        $expected = [
            'parameters' => $parameters,
            'hits' => $hits
        ];
        $this->assertEquals($expected, $result);
    }
    
    public function testJsonEncodeProducesValidJson(): void
    {
        // Arrange
        $parameters = ['divisor1' => 3, 'divisor2' => 5, 'limit' => 15, 'str1' => 'Fizz', 'str2' => 'Buzz'];
        $hits = 42;
        $dto = new FizzBuzzStatisticsDto($parameters, $hits);
        
        // Act
        $json = json_encode($dto);
        $decoded = json_decode($json, true);
        
        // Assert
        $this->assertNotFalse($json);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('parameters', $decoded);
        $this->assertArrayHasKey('hits', $decoded);
        $this->assertEquals($parameters, $decoded['parameters']);
        $this->assertEquals($hits, $decoded['hits']);
    }
} 