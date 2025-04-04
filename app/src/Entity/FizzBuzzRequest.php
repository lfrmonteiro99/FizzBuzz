<?php

namespace App\Entity;

use App\Repository\FizzBuzzRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FizzBuzzRequestRepository::class)]
#[ORM\Table(name: 'fizz_buzz_requests')]
#[ORM\UniqueConstraint(name: 'unique_request', columns: ['limit_value', '`int1`', '`int2`', 'str1', 'str2'])]
class FizzBuzzRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'limit_value')]
    #[Assert\Positive(message: 'The limit must be a positive number.')]
    private int $limit;

    #[ORM\Column(name: '`int1`')]
    #[Assert\Positive(message: 'The first divisor must be a positive number.')]
    private int $int1;

    #[ORM\Column(name: '`int2`')]
    #[Assert\Positive(message: 'The second divisor must be a positive number.')]
    #[Assert\NotEqualTo(
        propertyPath: 'int1',
        message: 'The divisors must be different.'
    )]
    private int $int2;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'The first string cannot be empty.')]
    private string $str1;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'The second string cannot be empty.')]
    private string $str2;

    #[ORM\Column]
    private int $hits = 1;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(int $limit, int $int1, int $int2, string $str1, string $str2)
    {
        $this->limit = $limit;
        $this->int1 = $int1;
        $this->int2 = $int2;
        $this->str1 = $str1;
        $this->str2 = $str2;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['limit'] ?? 0,
            $data['int1'] ?? 0,
            $data['int2'] ?? 0,
            $data['str1'] ?? '',
            $data['str2'] ?? ''
        );
    }

    public function incrementHits(): void
    {
        $this->hits++;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getInt1(): int
    {
        return $this->int1;
    }

    public function getInt2(): int
    {
        return $this->int2;
    }

    public function getStr1(): string
    {
        return $this->str1;
    }

    public function getStr2(): string
    {
        return $this->str2;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
} 