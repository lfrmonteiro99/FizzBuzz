<?php

namespace App\Tests\Service\Rule;

use App\Service\Rule\CombinedDivisibleRule;
use PHPUnit\Framework\TestCase;

class CombinedDivisibleRuleTest extends TestCase
{
    public function testAppliesTo(): void
    {
        $rule = new CombinedDivisibleRule(3, 5, 'olaadeus');
        
        // Numbers divisible by both 3 and 5
        $this->assertTrue($rule->appliesTo(15));
        $this->assertTrue($rule->appliesTo(30));
        $this->assertTrue($rule->appliesTo(45));
        
        // Numbers divisible by only one divisor
        $this->assertFalse($rule->appliesTo(3));
        $this->assertFalse($rule->appliesTo(5));
        $this->assertFalse($rule->appliesTo(9));
        $this->assertFalse($rule->appliesTo(10));
        
        // Numbers not divisible by either divisor
        $this->assertFalse($rule->appliesTo(1));
        $this->assertFalse($rule->appliesTo(2));
        $this->assertFalse($rule->appliesTo(4));
        $this->assertFalse($rule->appliesTo(7));
    }
    
    public function testGetReplacement(): void
    {
        $rule = new CombinedDivisibleRule(3, 5, 'olaadeus');
        $this->assertEquals('olaadeus', $rule->getReplacement());
        
        $rule = new CombinedDivisibleRule(2, 7, 'hellogoodbye');
        $this->assertEquals('hellogoodbye', $rule->getReplacement());
    }
    
    public function testWithZeroDivisor(): void
    {
        $rule = new CombinedDivisibleRule(0, 5, 'test');
        
        // Only zero should be divisible by zero
        $this->assertTrue($rule->appliesTo(0));
        $this->assertFalse($rule->appliesTo(5));
        $this->assertFalse($rule->appliesTo(10));
        
        // Test with both divisors as zero
        $rule = new CombinedDivisibleRule(0, 0, 'test');
        $this->assertTrue($rule->appliesTo(0));
        $this->assertFalse($rule->appliesTo(1));
    }
} 