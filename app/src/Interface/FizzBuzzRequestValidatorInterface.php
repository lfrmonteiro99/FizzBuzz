<?php

namespace App\Interface;

use App\Interface\FizzBuzzRequestInterface;

interface FizzBuzzRequestValidatorInterface
{
    /**
     * Validate a FizzBuzz request.
     *
     * @param FizzBuzzRequestInterface $request The request to validate
     * @throws \InvalidArgumentException If validation fails
     */
    public function validate(FizzBuzzRequestInterface $request): void;
} 