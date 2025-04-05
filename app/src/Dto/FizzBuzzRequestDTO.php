<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class FizzBuzzRequestDTO
{
    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    public int $limit;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    public int $int1;

    #[Assert\NotBlank]
    #[Assert\Type('integer')]
    #[Assert\Positive]
    public int $int2;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public string $str1;

    #[Assert\NotBlank]
    #[Assert\Type('string')]
    public string $str2;
} 