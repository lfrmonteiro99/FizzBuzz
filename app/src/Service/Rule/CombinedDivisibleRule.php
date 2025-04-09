<?php

namespace App\Service\Rule;

use App\Interface\SequenceRuleInterface;

class CombinedDivisibleRule implements SequenceRuleInterface
{
    public function __construct(
        private readonly int $divisor1,
        private readonly int $divisor2,
        private readonly string $replacement
    ) {
    }

    public function appliesTo(int $number): bool
    {
        // Check if the number is divisible by both divisors
        // Handle division by zero - avoid modulo by zero error
        if ($this->divisor1 === 0 || $this->divisor2 === 0) {
            return $number === 0; // Only zero is divisible by zero
        }
        
        return ($number % $this->divisor1 === 0) && ($number % $this->divisor2 === 0);
    }

    public function getReplacement(): string
    {
        return $this->replacement;
    }
} 