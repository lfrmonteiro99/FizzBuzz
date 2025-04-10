<?php

namespace App\Tests\Unit\Factory;

use App\Factory\DefaultSequenceRuleFactory;
use App\Interface\FizzBuzzRequestInterface;
use App\Service\Rule\DivisibleRule;
use App\Service\Rule\CombinedDivisibleRule;
use PHPUnit\Framework\TestCase;

class DefaultSequenceRuleFactoryTest extends TestCase
{
    private DefaultSequenceRuleFactory $factory;
    
    protected function setUp(): void
    {
        $this->factory = new DefaultSequenceRuleFactory();
    }
    
    public function testCreateRulesReturnsCorrectRules(): void
    {
        // Create a mock for FizzBuzzRequestInterface
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        
        // Set up the mock expectations
        $request->expects($this->exactly(2))
            ->method('getDivisor1')
            ->willReturn(3);
            
        $request->expects($this->exactly(2))
            ->method('getDivisor2')
            ->willReturn(5);
            
        $request->expects($this->exactly(2))
            ->method('getStr1')
            ->willReturn('fizz');
            
        $request->expects($this->exactly(2))
            ->method('getStr2')
            ->willReturn('buzz');
            
        // Call the method under test
        $rules = $this->factory->createRules($request);
        
        // Assert that three rules are returned
        $this->assertCount(3, $rules);
        
        // The first rule should be a CombinedDivisibleRule
        $this->assertInstanceOf(CombinedDivisibleRule::class, $rules[0]);
        // The rest should be DivisibleRule
        $this->assertInstanceOf(DivisibleRule::class, $rules[1]);
        $this->assertInstanceOf(DivisibleRule::class, $rules[2]);
        
        // Test the combined rule (divisor1 * divisor2)
        $this->assertTrue($rules[0]->appliesTo(15)); // 3 * 5 = 15
        $this->assertFalse($rules[0]->appliesTo(3));
        $this->assertFalse($rules[0]->appliesTo(5));
        $this->assertEquals('fizzbuzz', $rules[0]->getReplacement());
        
        // Test the first divisible rule (divisor1)
        $this->assertTrue($rules[1]->appliesTo(3));
        $this->assertFalse($rules[1]->appliesTo(5));
        $this->assertEquals('fizz', $rules[1]->getReplacement());
        
        // Test the second divisible rule (divisor2)
        $this->assertTrue($rules[2]->appliesTo(5));
        $this->assertFalse($rules[2]->appliesTo(3));
        $this->assertEquals('buzz', $rules[2]->getReplacement());
    }
    
    public function testCreateRulesWithDifferentValues(): void
    {
        // Create a mock for FizzBuzzRequestInterface with different values
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        
        // Set up the mock expectations
        $request->expects($this->exactly(2))
            ->method('getDivisor1')
            ->willReturn(2);
            
        $request->expects($this->exactly(2))
            ->method('getDivisor2')
            ->willReturn(7);
            
        $request->expects($this->exactly(2))
            ->method('getStr1')
            ->willReturn('even');
            
        $request->expects($this->exactly(2))
            ->method('getStr2')
            ->willReturn('seven');
            
        // Call the method under test
        $rules = $this->factory->createRules($request);
        
        // The first rule should be a CombinedDivisibleRule
        $this->assertInstanceOf(CombinedDivisibleRule::class, $rules[0]);
        
        // Test the combined rule
        $this->assertTrue($rules[0]->appliesTo(14)); // 2 * 7 = 14
        $this->assertEquals('evenseven', $rules[0]->getReplacement());
        
        // Test the divisible rules
        $this->assertTrue($rules[1]->appliesTo(2));
        $this->assertFalse($rules[1]->appliesTo(7));
        $this->assertEquals('even', $rules[1]->getReplacement());
        
        $this->assertTrue($rules[2]->appliesTo(7));
        $this->assertFalse($rules[2]->appliesTo(2));
        $this->assertEquals('seven', $rules[2]->getReplacement());
    }
} 