<?php

namespace App\Factory;

use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceRuleFactoryInterface;
use App\Interface\SequenceRuleInterface;
use App\Service\Rule\DivisibleRule;
use App\Service\Rule\CombinedDivisibleRule;

class DefaultSequenceRuleFactory implements SequenceRuleFactoryInterface
{
    public function createRules(FizzBuzzRequestInterface $request): array
    {
        return [
            // New combined rule that checks for divisibility by both divisors
            new CombinedDivisibleRule(
                $request->getDivisor1(),
                $request->getDivisor2(),
                $request->getStr1() . $request->getStr2()
            ),
            new DivisibleRule($request->getDivisor1(), $request->getStr1()),
            new DivisibleRule($request->getDivisor2(), $request->getStr2()),
        ];
    }
} 