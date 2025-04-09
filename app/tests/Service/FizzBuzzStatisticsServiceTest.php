<?php

namespace App\Tests\Service;

use App\Dto\FizzBuzzRequestDto;
use App\Dto\FizzBuzzStatisticsDto;
use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use App\Service\FizzBuzzStatisticsService;
use PHPUnit\Framework\TestCase;

class FizzBuzzStatisticsServiceTest extends TestCase
{
    private FizzBuzzStatisticsService $service;
    private FizzBuzzRequestRepositoryInterface $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(FizzBuzzRequestRepositoryInterface::class);
        $this->service = new FizzBuzzStatisticsService($this->repository);
    }

    public function testTrackRequest(): void
    {
        $dto = new FizzBuzzRequestDto([
            'limit' => 100,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz',
        ]);

        $this->repository->expects($this->once())
            ->method('findOrCreateRequest')
            ->with($dto);

        $this->service->trackRequest($dto);
    }

    public function testGetMostFrequentRequestWhenNoRequestsExist(): void
    {
        $this->repository->expects($this->once())
            ->method('findMostFrequentRequest')
            ->willReturn(null);

        $result = $this->service->getMostFrequentRequest();

        $this->assertInstanceOf(FizzBuzzStatisticsDto::class, $result);
        $this->assertNull($result->getParameters());
        $this->assertNull($result->getHits());
        $this->assertEquals('No requests have been made yet', $result->getMessage());
    }

    public function testGetMostFrequentRequestWhenRequestsExist(): void
    {
        $request = new FizzBuzzRequest(100, 3, 5, 'Fizz', 'Buzz');
        $request->incrementHits();
        $request->incrementHits();

        $this->repository->expects($this->once())
            ->method('findMostFrequentRequest')
            ->willReturn($request);

        $result = $this->service->getMostFrequentRequest();

        $this->assertInstanceOf(FizzBuzzStatisticsDto::class, $result);
        $this->assertEquals([
            'limit' => 100,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz',
        ], $result->getParameters());
        $this->assertEquals(2, $result->getHits());
        $this->assertNull($result->getMessage());
    }
} 