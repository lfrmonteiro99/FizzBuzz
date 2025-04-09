<?php

namespace App\Tests\Unit\Service;

use App\Entity\FizzBuzzRequest;
use App\Factory\FizzBuzzRequestFactory;
use App\Interface\FizzBuzzRequestInterface;
use App\Service\SequenceCache;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SequenceCacheTest extends TestCase
{
    private SequenceCache $cache;
    private CacheInterface $cachePool;
    private LoggerInterface $logger;
    private FizzBuzzRequestInterface $request;

    protected function setUp(): void
    {
        $this->cachePool = $this->createMock(CacheInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = new SequenceCache($this->cachePool, $this->logger);
        
        // Create a mock request
        $this->request = $this->createMock(FizzBuzzRequestInterface::class);
        $this->request->method('getDivisor1')->willReturn(3);
        $this->request->method('getDivisor2')->willReturn(5);
        $this->request->method('getStart')->willReturn(1);
        $this->request->method('getLimit')->willReturn(15);
        $this->request->method('getStr1')->willReturn('Fizz');
        $this->request->method('getStr2')->willReturn('Buzz');
    }

    public function testGet(): void
    {
        // Arrange
        $expectedCacheKey = $this->getCacheKeyForMockRequest();
        $expectedSequence = ['1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz'];
        
        // Create a callback for the cache get method
        $this->cachePool->method('get')
            ->willReturnCallback(function ($key, $callback) use ($expectedSequence) {
                return $expectedSequence;
            });
        
        // Act
        $result = $this->cache->get($this->request);
        
        // Assert
        $this->assertSame($expectedSequence, $result);
    }
    
    public function testGetWithMiss(): void
    {
        // Arrange - Set up a null result to simulate cache miss
        $this->cachePool->method('get')
            ->willReturnCallback(function ($key, $callback) {
                // Call the callback to simulate cache behavior
                $item = $this->createMock(ItemInterface::class);
                $item->method('expiresAfter')->willReturn($item);
                return $callback($item);
            });
        
        // Act
        $result = $this->cache->get($this->request);
        
        // Assert
        $this->assertNull($result);
    }

    public function testGetWithException(): void
    {
        // Arrange - Set up to throw an exception
        $this->cachePool->method('get')
            ->willThrowException(new \Exception('Cache error'));
            
        // Expect error to be logged
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error getting from cache', $this->anything());
            
        // Act
        $result = $this->cache->get($this->request);
        
        // Assert - should return null on error
        $this->assertNull($result);
    }
    
    public function testSet(): void
    {
        // Arrange
        $sequence = ['1', '2', 'Fizz', '4', 'Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz', '11', 'Fizz', '13', '14', 'FizzBuzz'];
        
        // We expect delete to be called first
        $this->cachePool->expects($this->once())
            ->method('delete')
            ->with($this->anything());
            
        // Then get to be called with our callback
        $this->cachePool->expects($this->once())
            ->method('get')
            ->with(
                $this->anything(),
                $this->anything()
            )
            ->willReturnCallback(function ($key, $callback) use ($sequence) {
                $item = $this->createMock(ItemInterface::class);
                $item->expects($this->once())
                    ->method('expiresAfter')
                    ->with(3600)
                    ->willReturn($item);
                
                return $callback($item);
            });
            
        // Act
        $this->cache->set($this->request, $sequence);
        
        // Assert - done through expectations
    }
    
    public function testClear(): void
    {
        // We expect delete to be called
        $this->cachePool->expects($this->once())
            ->method('delete')
            ->with($this->anything());
            
        // Act
        $this->cache->clear($this->request);
        
        // Assert - done through expectations
    }

    public function testClearWithNullRequest(): void
    {
        // Cache delete should not be called
        $this->cachePool->expects($this->never())
            ->method('delete');
            
        // Logger should record info message
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cache clearing is not supported for the full cache');
            
        // Act
        $this->cache->clear(null);
        
        // Assert - done through expectations
    }

    public function testClearWithException(): void
    {
        // Setup to throw an exception on delete
        $this->cachePool->method('delete')
            ->willThrowException(new \Exception('Cache error'));
            
        // Expect error to be logged
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Error clearing specific cache key', $this->anything());
            
        // Act - should not throw exception
        $this->cache->clear($this->request);
        
        // Assert - if we got here, the test passed
        $this->assertTrue(true);
    }
    
    public function testGenerateCacheKey(): void
    {
        // Create a FizzBuzzRequest mock to test with
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(15);
        $request->method('getStr1')->willReturn('Fizz');
        $request->method('getStr2')->willReturn('Buzz');
        
        // Invoke the method using reflection
        $reflectionClass = new \ReflectionClass(SequenceCache::class);
        $method = $reflectionClass->getMethod('generateCacheKey');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->cache, $request);
        
        // We know it should be a 32 character md5 hash
        $this->assertIsString($result);
        $this->assertEquals(32, strlen($result));
        
        // Compare with our manual hash calculation
        $expectedHash = md5(serialize([
            'start' => 1,
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ]));
        $this->assertEquals($expectedHash, $result);
    }
    
    public function testHandlesCacheErrors(): void
    {
        // Arrange
        $sequence = ['1', '2', 'Fizz', '4', 'Buzz'];
        
        // Setup to throw an exception on delete
        $this->cachePool->method('delete')
            ->willThrowException(new \Exception('Cache error'));
            
        // Expect error to be logged
        $this->logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error'), $this->anything());
            
        // Act - should not throw exception
        $this->cache->set($this->request, $sequence);
        
        // Assert - if we got here, the test passed
        $this->assertTrue(true);
    }
    
    private function getCacheKeyForMockRequest(): string
    {
        $requestParams = [
            'start' => 1,
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz'
        ];
        
        return md5(serialize($requestParams));
    }
} 