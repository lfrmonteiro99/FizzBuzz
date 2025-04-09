<?php

namespace App\Interface;

interface FizzBuzzRequestInterface
{
    /**
     * Get the starting number of the sequence.
     *
     * @return int The starting number
     */
    public function getStart(): int;

    /**
     * Get the upper limit of the sequence.
     *
     * @return int The upper limit
     */
    public function getLimit(): int;

    /**
     * Get the first divisor.
     *
     * @return int The first divisor
     */
    public function getDivisor1(): int;

    /**
     * Get the second divisor.
     *
     * @return int The second divisor
     */
    public function getDivisor2(): int;

    /**
     * Get the string to use for multiples of the first divisor.
     *
     * @return string The first string
     */
    public function getStr1(): string;

    /**
     * Get the string to use for multiples of the second divisor.
     *
     * @return string The second string
     */
    public function getStr2(): string;
} 