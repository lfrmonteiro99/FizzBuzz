<?php

namespace App\Interface;

use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceRuleInterface;

interface SequenceRuleFactoryInterface
{
    /**
     * Create rules based on the FizzBuzz request.
     *
     * @param FizzBuzzRequestInterface $request The FizzBuzz request
     * @return array<SequenceRuleInterface> The created rules
     */
    public function createRules(FizzBuzzRequestInterface $request): array;
} 