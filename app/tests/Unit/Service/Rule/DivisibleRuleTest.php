<?php

namespace App\Tests\Unit\Service\Rule;

use App\Service\Rule\DivisibleRule;
use PHPUnit\Framework\TestCase;

class DivisibleRuleTest extends TestCase
{
    /**
     * @dataProvider provideDivisibilityTestCases
     */
    public function testAppliesToReturnsCorrectValue(int $divisor, int $number, bool $expected): void
    {
        // Arrange
        $rule = new DivisibleRule($divisor, 'test');
        
        // Act
        $result = $rule->appliesTo($number);
        
        // Assert
        $this->assertSame($expected, $result);
    }
    
    public function provideDivisibilityTestCases(): array
    {
        return [
            'Number is divisible' => [3, 9, true],
            'Number is divisible (same number)' => [5, 5, true],
            'Number is divisible (zero)' => [5, 0, true],
            'Number is not divisible' => [3, 5, false],
            'Divisor 1 always applies' => [1, 42, true],
            'Divisor 1 always applies (negative)' => [1, -7, true],
            'Larger divisor' => [100, 200, true],
            'Larger divisor not divisible' => [100, 201, false],
            'Negative divisor' => [-3, -9, true],
            'Negative divisor with positive number' => [-3, 9, true],
            'Negative number not divisible' => [3, -10, false],
        ];
    }
    
    /**
     * @dataProvider provideReplacementTestCases
     */
    public function testGetReplacementReturnsCorrectString(string $replacement): void
    {
        // Arrange
        $rule = new DivisibleRule(3, $replacement);
        
        // Act
        $result = $rule->getReplacement();
        
        // Assert
        $this->assertSame($replacement, $result);
    }
    
    public function provideReplacementTestCases(): array
    {
        return [
            'Standard string' => ['fizz'],
            'Empty string' => [''],
            'Special characters' => ['!@#$%^'],
            'Number string' => ['123'],
            'Mixed content' => ['fizz123!'],
            'Unicode characters' => ['🚀💯'],
            'Long string' => [str_repeat('a', 100)],
        ];
    }
    
    public function testDivisibleRuleImplementsSequenceRuleInterface(): void
    {
        // Arrange
        $rule = new DivisibleRule(3, 'test');
        
        // Assert
        $this->assertInstanceOf('App\Interface\SequenceRuleInterface', $rule);
    }
    
    public function testEdgeCaseZeroDivisor(): void
    {
        // Create a rule with divisor of 0
        $rule = new DivisibleRule(0, 'zero');
        
        // Zero should be considered divisible by zero
        $this->assertTrue($rule->appliesTo(0), "0 should be considered divisible by 0");
        
        // Non-zero numbers should not be considered divisible by zero
        $this->assertFalse($rule->appliesTo(5), "5 should not be considered divisible by 0");
        $this->assertFalse($rule->appliesTo(-10), "-10 should not be considered divisible by 0");
        $this->assertFalse($rule->appliesTo(1000), "1000 should not be considered divisible by 0");
    }
} 