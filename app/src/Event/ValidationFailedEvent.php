<?php

namespace App\Event;

use App\Interface\FizzBuzzRequestInterface;

class ValidationFailedEvent extends BaseFizzBuzzEvent
{
    public const EVENT_NAME = 'fizzbuzz.validation.failed';

    /**
     * @var array
     */
    private array $errors;

    public function __construct(
        FizzBuzzRequestInterface $request,
        array $errors,
        array $context = []
    ) {
        $this->errors = $errors;
        parent::__construct($request, array_merge($context, [
            'errors' => $errors
        ]));
    }

    public function getEventName(): string
    {
        return self::EVENT_NAME;
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
} 