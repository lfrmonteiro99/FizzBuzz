<?php

namespace App\Tests\Controller;

use App\Interface\FizzBuzzStatisticsServiceInterface;
use App\Repository\FizzBuzzRequestRepository;
use App\Entity\FizzBuzzRequest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class FizzBuzzStatisticsControllerTest extends WebTestCase
{
    public function testGetMostFrequentRequestWhenNoRequests(): void
    {
        $client = static::createClient();
        
        // Create mock repository
        $repository = $this->createMock(FizzBuzzRequestRepository::class);
        $repository->method('findMostFrequentRequest')
            ->willReturn(null);
            
        // Set mock in container
        $client->getContainer()->set(FizzBuzzRequestRepository::class, $repository);

        // Make request
        $client->request('GET', '/fizzbuzz/statistics');

        // Assert response
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonStringEqualsJsonString(
            '{"message":"No requests have been made yet"}',
            $client->getResponse()->getContent()
        );
    }

    public function testGetMostFrequentRequest(): void
    {
        $client = static::createClient();
        
        // Create a FizzBuzzRequest entity
        $request = new FizzBuzzRequest(5, 3, 5, 'Fizz', 'Buzz');
        for ($i = 1; $i < 10; $i++) {
            $request->incrementHits();
        }
        
        // Create mock repository
        $repository = $this->createMock(FizzBuzzRequestRepository::class);
        $repository->method('findMostFrequentRequest')
            ->willReturn($request);
            
        // Set mock in container
        $client->getContainer()->set(FizzBuzzRequestRepository::class, $repository);

        // Make request
        $client->request('GET', '/fizzbuzz/statistics');

        // Assert response
        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertJsonStringEqualsJsonString(
            '{"parameters":{"limit":5,"int1":3,"int2":5,"str1":"Fizz","str2":"Buzz"},"hits":10}',
            $client->getResponse()->getContent()
        );
    }
} 