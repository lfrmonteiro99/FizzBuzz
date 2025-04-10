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

        // Handle invalid range
        if ($request->getStart() > $request->getLimit()) {
            return [];
        }

        $result = [];
        for ($i = $request->getStart(); $i <= $request->getLimit(); $i++) {
            $matchedReplacements = [];
            $combinedRuleApplied = false;
            
            // Check if any CombinedDivisibleRule applies first
            foreach ($this->rules as $rule) {
                if ($rule instanceof CombinedDivisibleRule && $rule->appliesTo($i)) {
                    $replacement = $rule->getReplacement();
                    if (!empty($replacement)) {
                        // For numbers divisible by both divisors, we only use the combined rule
                        $result[] = $replacement;
                        $combinedRuleApplied = true;
                        break;
                    }
                }
            }
            
            // Skip to next number if a combined rule was applied
            if ($combinedRuleApplied) {
                continue;
            }
            
            // Check all other rules that apply
            foreach ($this->rules as $rule) {
                if (!($rule instanceof CombinedDivisibleRule) && $rule->appliesTo($i)) {
                    $replacement = $rule->getReplacement();
                    if (!empty($replacement)) {
                        $matchedReplacements[] = $replacement;
                    }
                }
            }
            
            // If no rules matched or all replacements were empty, use the number itself
            if (empty($matchedReplacements)) {
                $result[] = (string)$i;
            } else {
                // For custom rules, concatenate all replacements when multiple apply
                $result[] = implode('', $matchedReplacements);
            }
        }

        return $result;
    }
} 