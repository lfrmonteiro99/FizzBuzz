<?php

namespace App\Request;

use Symfony\Component\Validator\Constraints as Assert;

class ValidatedFizzBuzzRequest extends BaseFizzBuzzRequest
{
    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    protected readonly int $start;

    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    #[Assert\GreaterThanOrEqual(propertyPath: 'start')]
    protected readonly int $limit;

    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    protected readonly int $divisor1;

    #[Assert\Type('integer')]
    #[Assert\GreaterThan(0)]
    protected readonly int $divisor2;

    #[Assert\Type('string')]
    #[Assert\NotBlank]
    protected readonly string $str1;

    #[Assert\Type('string')]
    #[Assert\NotBlank]
    protected readonly string $str2;
} 