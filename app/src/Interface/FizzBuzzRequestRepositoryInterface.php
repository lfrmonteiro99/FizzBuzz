<?php

namespace App\Interface;

use App\Entity\FizzBuzzRequest;

/**
 * Interface for FizzBuzzRequest repository operations
 */
interface FizzBuzzRequestRepositoryInterface
{
    /**
     * Find or create a FizzBuzz request with the given parameters
     * If the request already exists, increment its hit counter
     * If not, create a new request
     *
     * @param int $limit The upper limit
     * @param int $int1 The first divisor
     * @param int $int2 The second divisor
     * @param string $str1 The string for the first divisor
     * @param string $str2 The string for the second divisor
     * @return FizzBuzzRequest The request entity
     */
    public function findOrCreateRequest(int $limit, int $int1, int $int2, string $str1, string $str2): FizzBuzzRequest;
    
    /**
     * Find the most frequently requested FizzBuzz configuration
     *
     * @return FizzBuzzRequest|null The most frequent request or null if no requests exist
     */
    public function findMostFrequentRequest(): ?FizzBuzzRequest;
} 