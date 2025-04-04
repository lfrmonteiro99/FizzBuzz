<?php

namespace App\Dto;

class FizzBuzzStatisticsResponse
{
    public function __construct(
        public readonly ?array $parameters,
        public readonly ?int $hits,
        public readonly ?string $message
    ) {
    }

    public static function fromRequestData(?array $parameters, ?int $hits): self
    {
        if ($parameters === null || $hits === null) {
            return new self(null, null, 'No requests have been made yet');
        }

        return new self($parameters, $hits, null);
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
} 