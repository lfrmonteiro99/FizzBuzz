<?php

namespace App\Message;

use App\Interface\LogMessageInterface;

class LogMessage implements LogMessageInterface
{
    public function __construct(
        private readonly string $level,
        private readonly string $message,
        private readonly array $context = []
    ) {
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getContext(): array
    {
        return $this->context;
    }
} 