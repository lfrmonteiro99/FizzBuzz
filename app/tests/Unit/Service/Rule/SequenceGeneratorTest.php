<?php

namespace App\Tests\Unit\Service\Rule;

use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceRuleFactoryInterface;
use App\Interface\SequenceRuleInterface;
use App\Service\SequenceGenerator;
use PHPUnit\Framework\TestCase;

class SequenceGeneratorTest extends TestCase
{
    private SequenceGenerator $generator;
    private SequenceRuleFactoryInterface $ruleFactory;
    
    protected function setUp(): void
    {
        $this->ruleFactory = $this->createMock(SequenceRuleFactoryInterface::class);
        $this->generator = new SequenceGenerator($this->ruleFactory);
    }
    
    public function testGenerateWithDefaultRules(): void
    {
        // Create mock request
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(5);
        
        // Create mock rules
        $rule1 = $this->createMock(SequenceRuleInterface::class);
        $rule1->method('appliesTo')->willReturnCallback(function($num) {
            return $num % 3 === 0;
        });
        $rule1->method('getReplacement')->willReturn('Fizz');
        
        $rule2 = $this->createMock(SequenceRuleInterface::class);
        $rule2->method('appliesTo')->willReturnCallback(function($num) {
            return $num % 5 === 0;
        });
        $rule2->method('getReplacement')->willReturn('Buzz');
        
        // Setup rule factory
        $this->ruleFactory->expects($this->once())
            ->method('createRules')
            ->with($request)
            ->willReturn([$rule1, $rule2]);
        
        // Execute
        $result = $this->generator->generate($request);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals(['1', '2', 'Fizz', '4', 'Buzz'], $result);
    }
    
    public function testGenerateWithCustomRules(): void
    {
        // Create mock request
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(10);
        
        // Create mock rule for even numbers
        $evenRule = $this->createMock(SequenceRuleInterface::class);
        $evenRule->method('appliesTo')->willReturnCallback(function($num) {
            return $num % 2 === 0;
        });
        $evenRule->method('getReplacement')->willReturn('Even');
        
        // Create mock rule for numbers divisible by 3
        $threeRule = $this->createMock(SequenceRuleInterface::class);
        $threeRule->method('appliesTo')->willReturnCallback(function($num) {
            return $num % 3 === 0;
        });
        $threeRule->method('getReplacement')->willReturn('Three');
        
        // Manually add the rules
        $this->generator->addRule($evenRule);
        $this->generator->addRule($threeRule);
        
        // Execute - should use the manually added rules
        $result = $this->generator->generate($request);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(10, $result);
        $this->assertEquals([
            '1', 
            'Even', 
            'Three', 
            'Even', 
            '5', 
            'EvenThree', // Both rules apply
            '7', 
            'Even', 
            'Three', 
            'Even'
        ], $result);
    }
    
    public function testGenerateWithCustomStartAndLimit(): void
    {
        // Create mock request
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(5);
        $request->method('getLimit')->willReturn(10);
        
        // Create mock rules
        $rule1 = $this->createMock(SequenceRuleInterface::class);
        $rule1->method('appliesTo')->willReturnCallback(function($num) {
            return $num % 3 === 0;
        });
        $rule1->method('getReplacement')->willReturn('Fizz');
        
        $rule2 = $this->createMock(SequenceRuleInterface::class);
        $rule2->method('appliesTo')->willReturnCallback(function($num) {
            return $num % 5 === 0;
        });
        $rule2->method('getReplacement')->willReturn('Buzz');
        
        // Setup rule factory
        $this->ruleFactory->expects($this->once())
            ->method('createRules')
            ->with($request)
            ->willReturn([$rule1, $rule2]);
        
        // Execute
        $result = $this->generator->generate($request);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(6, $result); // 5, 6, 7, 8, 9, 10
        $this->assertEquals(['Buzz', 'Fizz', '7', '8', 'Fizz', 'Buzz'], $result);
    }
    
    public function testGenerateWithNoRulesApplying(): void
    {
        // Create mock request
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(5);
        
        // Create a rule that never applies
        $rule = $this->createMock(SequenceRuleInterface::class);
        $rule->method('appliesTo')->willReturn(false);
        $rule->method('getReplacement')->willReturn('Should not appear');
        
        // Setup rule factory
        $this->ruleFactory->expects($this->once())
            ->method('createRules')
            ->with($request)
            ->willReturn([$rule]);
        
        // Execute
        $result = $this->generator->generate($request);
        
        // Assert
        $this->assertIsArray($result);
        $this->assertCount(5, $result);
        $this->assertEquals(['1', '2', '3', '4', '5'], $result);
    }
} 