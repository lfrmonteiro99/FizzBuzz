<?php

namespace App\Tests\Unit\Factory;

use App\Factory\FizzBuzzResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class FizzBuzzResponseFactoryTest extends TestCase
{
    private FizzBuzzResponseFactory $factory;
    
    protected function setUp(): void
    {
        $this->factory = new FizzBuzzResponseFactory();
    }
    
    public function testCreateResponseReturnsJsonResponse(): void
    {
        // Arrange
        $sequence = ['1', '2', 'Fizz', '4', 'Buzz'];
        
        // Act
        $response = $this->factory->createResponse($sequence);
        
        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
    }
    
    public function testCreateResponseContainsSequenceAndCount(): void
    {
        // Arrange
        $sequence = ['1', '2', 'Fizz', '4', 'Buzz'];
        
        // Act
        $response = $this->factory->createResponse($sequence);
        
        // Assert
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('sequence', $content);
        $this->assertArrayHasKey('count', $content);
        $this->assertEquals($sequence, $content['sequence']);
        $this->assertEquals(count($sequence), $content['count']);
    }
    
    public function testCreateResponseWithEmptySequence(): void
    {
        // Arrange
        $sequence = [];
        
        // Act
        $response = $this->factory->createResponse($sequence);
        
        // Assert
        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('sequence', $content);
        $this->assertArrayHasKey('count', $content);
        $this->assertEquals([], $content['sequence']);
        $this->assertEquals(0, $content['count']);
    }
    
    public function testResponseHasCorrectContentType(): void
    {
        // Arrange
        $sequence = ['1', '2', 'Fizz', '4', 'Buzz'];
        
        // Act
        $response = $this->factory->createResponse($sequence);
        
        // Assert
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }
} 