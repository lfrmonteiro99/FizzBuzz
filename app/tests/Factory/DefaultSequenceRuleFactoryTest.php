<?php

namespace App\Tests\Factory;

use App\Factory\DefaultSequenceRuleFactory;
use App\Interface\FizzBuzzRequestInterface;
use App\Service\Rule\CombinedDivisibleRule;
use App\Service\Rule\DivisibleRule;
use PHPUnit\Framework\TestCase;

class DefaultSequenceRuleFactoryTest extends TestCase
{
    public function testCreateRules(): void
    {
        $factory = new DefaultSequenceRuleFactory();
        
        // Create a mock for FizzBuzzRequestInterface
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('ola');
        $request->method('getStr2')->willReturn('adeus');
        
        $rules = $factory->createRules($request);
        
        // Assert that we have 3 rules
        $this->assertCount(3, $rules);
        
        // First rule should be CombinedDivisibleRule
        $this->assertInstanceOf(CombinedDivisibleRule::class, $rules[0]);
        
        // Second and third rules should be DivisibleRule
        $this->assertInstanceOf(DivisibleRule::class, $rules[1]);
        $this->assertInstanceOf(DivisibleRule::class, $rules[2]);
        
        // Check divisors and replacements for second and third rules
        $this->assertEquals(3, $rules[1]->getDivisor());
        $this->assertEquals('ola', $rules[1]->getReplacement());
        
        $this->assertEquals(5, $rules[2]->getDivisor());
        $this->assertEquals('adeus', $rules[2]->getReplacement());
    }
} 