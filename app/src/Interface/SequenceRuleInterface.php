<?php

namespace App\Interface;

interface SequenceRuleInterface
{
    /**
     * Check if the rule applies to the given number.
     *
     * @param int $number The number to check
     * @return bool Whether the rule applies
     */
    public function appliesTo(int $number): bool;

    /**
     * Get the replacement value for the number.
     *
     * @return string The replacement value
     */
    public function getReplacement(): string;
} 