<?php

namespace App\Request;

use App\Interface\FizzBuzzRequestInterface;

abstract class BaseFizzBuzzRequest implements FizzBuzzRequestInterface
{
    public function __construct(
        protected readonly int $start,
        protected readonly int $limit,
        protected readonly int $divisor1,
        protected readonly int $divisor2,
        protected readonly string $str1,
        protected readonly string $str2
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