<?php

namespace App\Entity;

use App\Repository\FizzBuzzRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FizzBuzzRequestRepository::class)]
#[ORM\Table(name: 'fizz_buzz_requests')]
#[ORM\UniqueConstraint(name: 'unique_request', columns: ['start', 'limit_value', 'divisor1', 'divisor2', 'str1', 'str2'])]
#[ORM\Index(name: 'idx_hits_created', columns: ['hits', 'created_at'], options: ['order' => ['hits' => 'DESC', 'created_at' => 'DESC']])]
#[ORM\Index(name: 'idx_created_processed', columns: ['created_at', 'processed_at'])]
#[ORM\Index(name: 'idx_processed_hits', columns: ['processed_at', 'hits'], options: ['order' => ['processed_at' => 'DESC', 'hits' => 'DESC']])]
#[ORM\Cache(usage: 'READ_ONLY', region: 'fizzbuzz_requests')]
class FizzBuzzRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'The start must be a positive number.')]
    private int $start = 1;

    #[ORM\Column(name: 'limit_value')]
    #[Assert\Positive(message: 'The limit must be a positive number.')]
    #[Assert\LessThanOrEqual(
        value: 1000,
        message: 'The limit cannot be greater than 1000.'
    )]
    private int $limit;

    #[ORM\Column(name: 'divisor1')]
    #[Assert\Positive(message: 'The first divisor must be a positive number.')]
    #[Assert\LessThanOrEqual(
        value: 100,
        message: 'The first divisor cannot be greater than 100.'
    )]
    private int $divisor1;

    #[ORM\Column(name: 'divisor2')]
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

    #[ORM\Column(length: 255)]
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

    #[ORM\Column(length: 255)]
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

    #[ORM\Column]
    private int $hits = 0;

    #[ORM\Column(type: 'integer')]
    #[ORM\Version]
    private int $version = 1;

    #[ORM\Column(length: 20)]
    private string $trackingState = 'pending';

    #[ORM\Column(name: 'created_at')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'processed_at', nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    public function __construct(
        int $limit,
        int $divisor1,
        int $divisor2,
        string $str1,
        string $str2,
        int $start = 1
    ) {
        $this->limit = $limit;
        $this->divisor1 = $divisor1;
        $this->divisor2 = $divisor2;
        $this->str1 = $str1;
        $this->str2 = $str2;
        $this->start = $start;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->hits = 0;
        $this->version = 1;
        $this->trackingState = 'pending';
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int)($data['limit'] ?? 0),
            (int)($data['divisor1'] ?? 0),
            (int)($data['divisor2'] ?? 0),
            (string)($data['str1'] ?? ''),
            (string)($data['str2'] ?? ''),
            (int)($data['start'] ?? 1)
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

    public function getStart(): int
    {
        return $this->start;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): static
    {
        $this->limit = $limit;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDivisor1(): int
    {
        return $this->divisor1;
    }

    public function setDivisor1(int $divisor1): static
    {
        $this->divisor1 = $divisor1;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getDivisor2(): int
    {
        return $this->divisor2;
    }

    public function setDivisor2(int $divisor2): static
    {
        $this->divisor2 = $divisor2;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStr1(): string
    {
        return $this->str1;
    }

    public function setStr1(string $str1): static
    {
        $this->str1 = $str1;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getStr2(): string
    {
        return $this->str2;
    }

    public function setStr2(string $str2): static
    {
        $this->str2 = $str2;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getHits(): int
    {
        return $this->hits;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function getTrackingState(): string
    {
        return $this->trackingState;
    }

    public function markAsProcessed(): void
    {
        $this->trackingState = 'processed';
        $this->processedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsFailed(): void
    {
        $this->trackingState = 'failed';
        $this->processedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }
} 