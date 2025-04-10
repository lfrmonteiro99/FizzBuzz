<?php

namespace App\Service\Rule;

use App\Interface\SequenceRuleInterface;

class CombinedDivisibleRule extends DivisibleRule implements SequenceRuleInterface
{
    private readonly int $divisor2;
    private readonly string $replacement;

    public function __construct(
        int $divisor1,
        int $divisor2,
        string $replacement
    ) {
        parent::__construct($divisor1, ""); // Empty replacement for parent
        $this->divisor2 = $divisor2;
        $this->replacement = $replacement;
    }

    public function appliesTo(int $number): bool
    {
        // Check if the number is divisible by both divisors
        // Handle division by zero - avoid modulo by zero error
        if ($this->getDivisor() === 0 || $this->divisor2 === 0) {
            return $number === 0; // Only zero is divisible by zero
        }
        
        return ($number % $this->getDivisor() === 0) && ($number % $this->divisor2 === 0);
    }

    public function getReplacement(): string
    {
        // Override parent method to return the combined replacement
        return $this->replacement;
    }
} 