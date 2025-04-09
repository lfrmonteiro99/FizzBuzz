<?php

namespace App\Interface;

use App\Entity\FizzBuzzRequest;

interface SequenceGeneratorInterface
{
    /**
     * Generate a sequence based on the given request.
     *
     * @param FizzBuzzRequestInterface $request The request containing sequence parameters
     * @return array<string|int> The generated sequence
     */
    public function generate(FizzBuzzRequestInterface $request): array;
} 