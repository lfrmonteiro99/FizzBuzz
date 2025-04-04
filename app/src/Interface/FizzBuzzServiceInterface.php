<?php

namespace App\Interface;

use App\Entity\FizzBuzzRequest;

/**
 * Interface for FizzBuzz service
 */
interface FizzBuzzServiceInterface
{
    /**
     * Generate a FizzBuzz sequence based on the given parameters
     * @param FizzBuzzRequest $request The request containing FizzBuzz parameters
     * @return array The generated FizzBuzz sequence
     */
    public function generate(FizzBuzzRequest $request): array;
}