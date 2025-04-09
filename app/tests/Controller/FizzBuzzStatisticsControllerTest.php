<?php

namespace App\Tests\Controller;

use App\Dto\FizzBuzzStatisticsDto;
use App\Interface\FizzBuzzStatisticsServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FizzBuzzStatisticsControllerTest extends KernelTestCase
{
    private KernelBrowser $client;
    
    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->client = new KernelBrowser($kernel);
    }
    
    public function testGetStatisticsWithNoRequests(): void
    {
        // Create a mock of the statistics service that returns null
        $statisticsService = $this->createMock(FizzBuzzStatisticsServiceInterface::class);
        $statisticsService->method('getMostFrequentRequest')->willReturn(null);

        // Override the service in the container
        self::getContainer()->set(FizzBuzzStatisticsServiceInterface::class, $statisticsService);

        // Make the request using the client
        $this->client->request('GET', '/fizzbuzz/statistics');
        
        // Get the response after making the request
        $response = $this->client->getResponse();
        
        // Check that the response is successful
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        // Check the response content
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('success', $responseData['status']);
        $this->assertEquals('No FizzBuzz requests have been made yet.', $responseData['message']);
        $this->assertNull($responseData['data']);
    }
    
    public function testGetStatisticsWithExistingRequests(): void
    {
        // Create a mock statistics DTO
        $statisticsDto = new FizzBuzzStatisticsDto(
            parameters: [
                'start' => 1,
                'limit' => 15,
                'divisor1' => 3,
                'divisor2' => 5,
                'str1' => 'Fizz',
                'str2' => 'Buzz'
            ],
            hits: 42
        );
        
        // Create a mock of the statistics service that returns the DTO
        $statisticsService = $this->createMock(FizzBuzzStatisticsServiceInterface::class);
        $statisticsService->method('getMostFrequentRequest')->willReturn($statisticsDto);
        
        // Override the service in the container
        self::getContainer()->set(FizzBuzzStatisticsServiceInterface::class, $statisticsService);
        
        // Make the request using the client
        $this->client->request('GET', '/fizzbuzz/statistics');
        
        // Get the response after making the request
        $response = $this->client->getResponse();
        
        // Check that the response is successful
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        
        // Check the response content
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('success', $responseData['status']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertArrayHasKey('most_frequent_request', $responseData['data']);
        
        $mostFrequentRequest = $responseData['data']['most_frequent_request'];
        $this->assertArrayHasKey('parameters', $mostFrequentRequest);
        $this->assertArrayHasKey('hits', $mostFrequentRequest);
        $this->assertEquals(42, $mostFrequentRequest['hits']);
        
        $parameters = $mostFrequentRequest['parameters'];
        $this->assertEquals(1, $parameters['start']);
        $this->assertEquals(15, $parameters['limit']);
        $this->assertEquals(3, $parameters['divisor1']);
        $this->assertEquals(5, $parameters['divisor2']);
        $this->assertEquals('Fizz', $parameters['str1']);
        $this->assertEquals('Buzz', $parameters['str2']);
    }
} 