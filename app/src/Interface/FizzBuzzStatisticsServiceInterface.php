<?php

namespace App\Interface;

use App\Dto\FizzBuzzRequestDto;
use App\Dto\FizzBuzzStatisticsDto;

interface FizzBuzzStatisticsServiceInterface
{
    /**
     * Track a FizzBuzz request in the statistics.
     *
     * @param FizzBuzzRequestDto $dto The DTO containing the request parameters
     */
    public function trackRequest(FizzBuzzRequestDto $dto): void;

    /**
     * Get the most frequent FizzBuzz request.
     *
     * @return FizzBuzzStatisticsDto|null The most frequent request, or null if no requests exist
     */
    public function getMostFrequentRequest(): ?FizzBuzzStatisticsDto;
} 