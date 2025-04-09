<?php

namespace App\Tests\Functional\Controller;

use App\Dto\FizzBuzzStatisticsDto;
use App\Interface\FizzBuzzStatisticsServiceInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @codeCoverageIgnore
 */
class FizzBuzzStatisticsControllerTest extends WebTestCase
{
    private $client;
    private $statisticsServiceMock;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        // Create mock for the service
        $this->statisticsServiceMock = $this->createMock(FizzBuzzStatisticsServiceInterface::class);

        // Replace the actual service with the mock in the container
        static::getContainer()->set(FizzBuzzStatisticsServiceInterface::class, $this->statisticsServiceMock);
    }
    
    public function testGetStatisticsWithNoRequests(): void
    {
        // Configure mock to return null
        $this->statisticsServiceMock->method('getMostFrequentRequest')->willReturn(null);

        $this->client->request('GET', '/fizzbuzz/statistics'); // Corrected Route

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('status', $responseContent);
        $this->assertEquals('success', $responseContent['status']);
        $this->assertArrayHasKey('message', $responseContent);
        $this->assertEquals('No FizzBuzz requests have been made yet.', $responseContent['message']);
        $this->assertArrayHasKey('data', $responseContent);
        $this->assertNull($responseContent['data']);
    }
    
    public function testGetStatisticsWithRequests(): void
    {
        // Create a sample DTO to be returned by the mock
        $expectedParams = [
            'limit' => 10,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => 1
        ];
        $expectedHits = 7;
        $statisticsDto = new FizzBuzzStatisticsDto($expectedParams, $expectedHits);

        // Configure mock to return the DTO
        $this->statisticsServiceMock->method('getMostFrequentRequest')->willReturn($statisticsDto);

        $this->client->request('GET', '/fizzbuzz/statistics'); // Corrected Route

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $responseContent = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($responseContent);
        $this->assertArrayHasKey('status', $responseContent);
        $this->assertEquals('success', $responseContent['status']);
        $this->assertArrayNotHasKey('message', $responseContent);
        $this->assertArrayHasKey('data', $responseContent);
        $this->assertIsArray($responseContent['data']);
        $this->assertArrayHasKey('most_frequent_request', $responseContent['data']);

        $mostFrequentData = $responseContent['data']['most_frequent_request'];
        $this->assertIsArray($mostFrequentData);
        $this->assertArrayHasKey('parameters', $mostFrequentData);
        $this->assertArrayHasKey('hits', $mostFrequentData);
        $this->assertEquals($expectedHits, $mostFrequentData['hits']);

        // Need to sort because DTO might not guarantee order internally
        ksort($expectedParams);
        ksort($mostFrequentData['parameters']);
        $this->assertEquals($expectedParams, $mostFrequentData['parameters']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up - helps prevent memory leaks
        $this->client = null;
        $this->statisticsServiceMock = null;
    }
} 