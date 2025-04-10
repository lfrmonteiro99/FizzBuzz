<?php

namespace App\Service;

use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceGeneratorInterface;
use App\Interface\SequenceRuleFactoryInterface;
use App\Interface\SequenceRuleInterface;
use App\Service\Rule\CombinedDivisibleRule;

class SequenceGenerator implements SequenceGeneratorInterface
{
    /**
     * @var array<SequenceRuleInterface>
     */
    private array $rules;

    public function __construct(
        private readonly SequenceRuleFactoryInterface $ruleFactory
    ) {
        $this->rules = [];
    }

    /**
     * Add a rule to the sequence generator.
     *
     * @param SequenceRuleInterface $rule The rule to add
     */
    public function addRule(SequenceRuleInterface $rule): void
    {
        $this->rules[] = $rule;
    }

    /**
     * Generate a FizzBuzz sequence based on the given request.
     *
     * @param FizzBuzzRequestInterface $request The request containing FizzBuzz parameters
     * @return array<string|int> The generated FizzBuzz sequence
     */
    public function generate(FizzBuzzRequestInterface $request): array
    {
        // Add default rules if none are set
        if (empty($this->rules)) {
            $this->rules = $this->ruleFactory->createRules($request);
        }

        $result = [];
        for ($i = $request->getStart(); $i <= $request->getLimit(); $i++) {
            $value = '';
            $ruleApplied = false;
            
            // First check for combined rules (which have priority)
            foreach ($this->rules as $rule) {
                if ($rule instanceof CombinedDivisibleRule && $rule->appliesTo($i)) {
                    $value = $rule->getReplacement(); // Just the replacement without duplicating
                    $ruleApplied = true;
                    break;
                }
            }
            
            // If no combined rule matched, check regular rules
            if (!$ruleApplied) {
                foreach ($this->rules as $rule) {
                    if (!($rule instanceof CombinedDivisibleRule) && $rule->appliesTo($i)) {
                        $value = $rule->getReplacement();
                        $ruleApplied = true;
                        break;
                    }
                }
            }
            
            // If no rules matched, use the number itself
            if (!$ruleApplied) {
                $value = (string)$i;
            }
            
            $result[] = $value;
        }

        return $result;
    }
} 