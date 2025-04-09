<?php

namespace App\Dto;

use App\Entity\FizzBuzzRequest;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    title: "FizzBuzzRequest",
    description: "Parameters for generating a FizzBuzz sequence"
)]
class FizzBuzzRequestDto
{
    #[OA\Property(description: "Upper limit of the sequence", minimum: 1, maximum: 1000)]
    #[Assert\Positive(message: 'The limit must be a positive number.')]
    #[Assert\LessThanOrEqual(
        value: 1000,
        message: 'The limit cannot be greater than 1000.'
    )]
    private int $limit;

    #[OA\Property(description: "First divisor", minimum: 1, maximum: 100)]
    #[Assert\Positive(message: 'The first divisor must be a positive number.')]
    #[Assert\LessThanOrEqual(
        value: 100,
        message: 'The first divisor cannot be greater than 100.'
    )]
    private int $divisor1;

    #[OA\Property(description: "Second divisor", minimum: 1, maximum: 100)]
    #[Assert\Positive(message: 'The second divisor must be a positive number.')]
    #[Assert\LessThanOrEqual(
        value: 100,
        message: 'The second divisor cannot be greater than 100.'
    )]
    #[Assert\NotEqualTo(
        propertyPath: 'divisor1',
        message: 'The divisors must be different.'
    )]
    private int $divisor2;

    #[OA\Property(description: "String to use for multiples of divisor1", minLength: 1, maxLength: 50)]
    #[Assert\NotBlank(message: 'The first string cannot be empty.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'The first string cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s]+$/',
        message: 'The first string can only contain letters, numbers, and spaces.'
    )]
    private string $str1;

    #[OA\Property(description: "String to use for multiples of divisor2", minLength: 1, maxLength: 50)]
    #[Assert\NotBlank(message: 'The second string cannot be empty.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'The second string cannot be longer than {{ limit }} characters.'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9\s]+$/',
        message: 'The second string can only contain letters, numbers, and spaces.'
    )]
    private string $str2;

    #[OA\Property(description: "Starting number of the sequence", minimum: 1, default: 1)]
    #[Assert\Positive(message: 'The start must be a positive number.')]
    private int $start = 1;

    public function __construct(array $data)
    {
        $this->limit = (int)($data['limit'] ?? 0);
        $this->divisor1 = (int)($data['divisor1'] ?? 0);
        $this->divisor2 = (int)($data['divisor2'] ?? 0);
        $this->str1 = (string)($data['str1'] ?? '');
        $this->str2 = (string)($data['str2'] ?? '');
        $this->start = (int)($data['start'] ?? 1);
    }

    public static function fromRequest(array $data): self
    {
        return new self($data);
    }

    public function toEntity(): FizzBuzzRequest
    {
        return new FizzBuzzRequest(
            $this->getLimit(),
            $this->getDivisor1(),
            $this->getDivisor2(),
            $this->getStr1(),
            $this->getStr2(),
            $this->getStart()
        );
    }

    public static function fromEntity(FizzBuzzRequest $entity): self
    {
        return new self([
            'start' => $entity->getStart(),
            'limit' => $entity->getLimit(),
            'divisor1' => $entity->getDivisor1(),
            'divisor2' => $entity->getDivisor2(),
            'str1' => $entity->getStr1(),
            'str2' => $entity->getStr2(),
        ]);
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

    public function toArray(): array
    {
        return [
            'start' => $this->start,
            'limit' => $this->limit,
            'divisor1' => $this->divisor1,
            'divisor2' => $this->divisor2,
            'str1' => $this->str1,
            'str2' => $this->str2,
        ];
    }
} 