<?php

namespace App\Interface;

interface FizzBuzzSequenceServiceInterface
{
    /**
     * Generate a FizzBuzz sequence based on the given request.
     *
     * @param FizzBuzzRequestInterface $request The FizzBuzz request
     * @return array The generated FizzBuzz sequence
     */
    public function generateSequence(FizzBuzzRequestInterface $request): array;
} 