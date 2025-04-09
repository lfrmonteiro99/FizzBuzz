<?php

namespace App\Service;

use App\Interface\FizzBuzzRequestInterface;
use App\Interface\FizzBuzzSequenceServiceInterface;
use App\Interface\SequenceGeneratorInterface;

class FizzBuzzSequenceService implements FizzBuzzSequenceServiceInterface
{
    public function __construct(
        private readonly SequenceGeneratorInterface $sequenceGenerator
    ) {
    }

    /**
     * Generate a FizzBuzz sequence based on the given request.
     *
     * @param FizzBuzzRequestInterface $request The FizzBuzz request
     * @return array The generated FizzBuzz sequence
     */
    public function generateSequence(FizzBuzzRequestInterface $request): array
    {
        return $this->sequenceGenerator->generate($request);
    }
} 