<?php

namespace App\Tests\Service;

use App\Dto\FizzBuzzStatisticsResponse;
use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use App\Service\FizzBuzzStatisticsService;
use PHPUnit\Framework\TestCase;

class FizzBuzzStatisticsServiceTest extends TestCase
{
    private $repository;
    private $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(FizzBuzzRequestRepositoryInterface::class);
        $this->service = new FizzBuzzStatisticsService($this->repository);
    }

    public function testTrackRequest(): void
    {
        // Create test data
        $limit = 15;
        $int1 = 3;
        $int2 = 5;
        $str1 = 'Fizz';
        $str2 = 'Buzz';
        
        // Create a mock FizzBuzzRequest that would be returned
        $fizzBuzzRequest = new FizzBuzzRequest($limit, $int1, $int2, $str1, $str2);
        
        // Set expectation on repository
        $this->repository->expects($this->once())
            ->method('findOrCreateRequest')
            ->with($limit, $int1, $int2, $str1, $str2)
            ->willReturn($fizzBuzzRequest);
        
        // Call the method
        $this->service->trackRequest($limit, $int1, $int2, $str1, $str2);
        
        // No assertions needed as we're verifying the expectation on the repository call
    }

    public function testGetMostFrequentRequestWithResult(): void
    {
        // Create a mock FizzBuzzRequest
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $request->incrementHits(); // Set hits to 2
        
        // Set expectation on repository
        $this->repository->expects($this->once())
            ->method('findMostFrequentRequest')
            ->willReturn($request);
        
        // Call the method
        $result = $this->service->getMostFrequentRequest();
        
        // Assert the result is correct
        $this->assertInstanceOf(FizzBuzzStatisticsResponse::class, $result);
        $expectedData = [
            'parameters' => [
                'limit' => 15,
                'int1' => 3,
                'int2' => 5,
                'str1' => 'Fizz',
                'str2' => 'Buzz',
            ],
            'hits' => 2,
        ];
        $this->assertEquals($expectedData, $result->toArray());
    }

    public function testGetMostFrequentRequestWithNoResult(): void
    {
        // Set expectation on repository to return null (no requests found)
        $this->repository->expects($this->once())
            ->method('findMostFrequentRequest')
            ->willReturn(null);
        
        // Call the method
        $result = $this->service->getMostFrequentRequest();
        
        // Assert the result is correct for no requests
        $this->assertInstanceOf(FizzBuzzStatisticsResponse::class, $result);
        $expectedData = [
            'message' => 'No requests have been made yet'
        ];
        $this->assertEquals($expectedData, $result->toArray());
    }
} 