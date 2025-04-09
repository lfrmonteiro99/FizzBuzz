<?php

namespace App\Request;

use App\Interface\FizzBuzzRequestInterface;

class FizzBuzzRequest implements FizzBuzzRequestInterface
{
    public function __construct(
        private readonly int $start,
        private readonly int $limit,
        private readonly int $divisor1,
        private readonly int $divisor2,
        private readonly string $str1,
        private readonly string $str2
    ) {
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getDivisor1(): int
    {
        return $this->divisor1;
    }

    public function getDivisor2(): int
    {
        return $this->divisor2;
    }

    public function getStr1(): string
    {
        return $this->str1;
    }

    public function getStr2(): string
    {
        return $this->str2;
    }
} 