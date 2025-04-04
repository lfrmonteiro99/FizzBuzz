<?php

namespace App\Service;

use App\Dto\FizzBuzzStatisticsResponse;
use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;

class FizzBuzzStatisticsService
{
    public function __construct(
        private readonly FizzBuzzRequestRepositoryInterface $repository
    ) {
    }

    /**
     * Track a FizzBuzz request in the statistics.
     *
     * @param positive-int $limit The upper limit of the sequence
     * @param positive-int $int1 The first divisor
     * @param positive-int $int2 The second divisor
     * @param non-empty-string $str1 The string to use for multiples of int1
     * @param non-empty-string $str2 The string to use for multiples of int2
     */
    public function trackRequest(int $limit, int $int1, int $int2, string $str1, string $str2): void
    {
        $this->repository->findOrCreateRequest($limit, $int1, $int2, $str1, $str2);
    }

    /**
     * Get the most frequent FizzBuzz request.
     *
     * @return FizzBuzzStatisticsResponse The statistics response containing the most frequent request
     */
    public function getMostFrequentRequest(): FizzBuzzStatisticsResponse
    {
        $request = $this->repository->findMostFrequentRequest();
        
        if ($request === null) {
            return FizzBuzzStatisticsResponse::fromRequestData(null, null);
        }

        return FizzBuzzStatisticsResponse::fromRequestData(
            [
                'limit' => $request->getLimit(),
                'int1' => $request->getInt1(),
                'int2' => $request->getInt2(),
                'str1' => $request->getStr1(),
                'str2' => $request->getStr2(),
            ],
            $request->getHits()
        );
    }
} 