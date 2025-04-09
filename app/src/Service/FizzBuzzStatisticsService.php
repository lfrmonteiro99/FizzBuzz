<?php

namespace App\Service;

use App\Dto\FizzBuzzRequestDto;
use App\Dto\FizzBuzzStatisticsDto;
use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use App\Interface\FizzBuzzStatisticsServiceInterface;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;

class FizzBuzzStatisticsService implements FizzBuzzStatisticsServiceInterface
{
    public function __construct(
        private readonly FizzBuzzRequestRepositoryInterface $repository,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Track a FizzBuzz request in the statistics.
     *
     * @param FizzBuzzRequestDto $dto The DTO containing the request parameters
     */
    public function trackRequest(FizzBuzzRequestDto $dto): void
    {
        try {
            $request = $this->repository->findOrCreateRequest($dto);
            $this->repository->incrementHits($request);
            $this->repository->markAsProcessed($request);
        } catch (OptimisticLockException $e) {
            $this->logger->error('Optimistic lock exception while tracking request', [
                'exception' => $e,
                'request' => $dto->toArray()
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('Error tracking request', [
                'exception' => $e,
                'request' => $dto->toArray()
            ]);
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMostFrequentRequest(): ?FizzBuzzStatisticsDto
    {
        try {
            $request = $this->repository->findMostFrequentRequest();
            if (!$request) {
                return FizzBuzzStatisticsDto::fromRequestData(null, null);
            }

            return FizzBuzzStatisticsDto::fromEntity($request);
        } catch (\Exception $e) {
            $this->logger->error('Error getting most frequent request', [
                'exception' => $e
            ]);
            throw $e;
        }
    }

    private function getStatsBySimilarCombination(FizzBuzzRequestInterface $currentRequest): ?FizzBuzzStatisticsDto
    {
        $similarRequests = $this->repository->findBySimilarCombination(
            $currentRequest->getDivisor1(),
            $currentRequest->getDivisor2(),
            $currentRequest->getStr1(),
            $currentRequest->getStr2()
        );
        
        if (empty($similarRequests)) {
            return null;
        }
        
        $totalHits = array_sum(array_map(fn($request) => $request->getHits(), $similarRequests));
        
        // Get the most frequently used limit for this combination
        $limitCounts = [];
        foreach ($similarRequests as $request) {
            $limit = $request->getLimit();
            if (!isset($limitCounts[$limit])) {
                $limitCounts[$limit] = 0;
            }
            $limitCounts[$limit] += $request->getHits();
        }
        
        arsort($limitCounts);
        $mostUsedLimit = key($limitCounts);
        
        return new FizzBuzzStatisticsDto(
            [
                'divisor1' => $currentRequest->getDivisor1(),
                'divisor2' => $currentRequest->getDivisor2(),
                'str1' => $currentRequest->getStr1(),
                'str2' => $currentRequest->getStr2(),
                'limit' => $mostUsedLimit
            ],
            $totalHits
        );
    }
} 