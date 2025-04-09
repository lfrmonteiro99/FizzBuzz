<?php

namespace App\Service\Rule;

use App\Interface\SequenceRuleInterface;

class DivisibleRule implements SequenceRuleInterface
{
    public function __construct(
        private readonly int $divisor,
        private readonly string $replacement
    ) {
    }

    public function appliesTo(int $number): bool
    {
        // Handle division by zero - avoid modulo by zero error
        if ($this->divisor === 0) {
            return $number === 0; // Only zero is divisible by zero
        }
        
        return $number % $this->divisor === 0;
    }

    public function getReplacement(): string
    {
        return $this->replacement;
    }

    public function getDivisor(): int
    {
        return $this->divisor;
    }
} 