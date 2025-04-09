<?php

namespace App\Dto;

use App\Entity\FizzBuzzRequest;
use Symfony\Component\Validator\Constraints as Assert;
use JsonSerializable;

class FizzBuzzStatisticsDto implements JsonSerializable
{
    #[Assert\Type(type: 'array', message: 'Parameters must be an array.')]
    private ?array $parameters;

    #[Assert\Type(type: 'integer', message: 'Hits must be an integer.')]
    #[Assert\PositiveOrZero(message: 'Hits cannot be negative.')]
    private ?int $hits;

    #[Assert\Type(type: 'string', message: 'Message must be a string.')]
    private ?string $message;

    public function __construct(?array $parameters, ?int $hits, ?string $message = null)
    {
        $this->parameters = $parameters;
        $this->hits = $hits;
        $this->message = $message;
    }

    public static function fromRequestData(?array $parameters, ?int $hits): self
    {
        if ($parameters === null || $hits === null) {
            return new self(null, null, 'No requests have been made yet');
        }

        return new self($parameters, $hits);
    }

    public static function fromEntity(FizzBuzzRequest $entity): self
    {
        return new self(
            [
                'limit' => $entity->getLimit(),
                'divisor1' => $entity->getDivisor1(),
                'divisor2' => $entity->getDivisor2(),
                'str1' => $entity->getStr1(),
                'str2' => $entity->getStr2(),
            ],
            $entity->getHits()
        );
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function getHits(): ?int
    {
        return $this->hits;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        if ($this->message !== null) {
            return ['message' => $this->message];
        }

        return [
            'parameters' => $this->parameters,
            'hits' => $this->hits
        ];
    }
    
    /**
     * Specify data which should be serialized to JSON
     */
    public function jsonSerialize(): mixed
    {
        if ($this->message !== null) {
            return ['message' => $this->message];
        }
        
        return [
            'parameters' => $this->parameters,
            'hits' => $this->hits
        ];
    }
} 