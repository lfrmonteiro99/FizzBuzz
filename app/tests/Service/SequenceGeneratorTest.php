<?php

namespace App\Tests\Service;

use App\Factory\DefaultSequenceRuleFactory;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceRuleFactoryInterface;
use App\Interface\SequenceRuleInterface;
use App\Service\SequenceGenerator;
use PHPUnit\Framework\TestCase;

class SequenceGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        // Create mock request
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(15);
        $request->method('getDivisor1')->willReturn(3);
        $request->method('getDivisor2')->willReturn(5);
        $request->method('getStr1')->willReturn('ola');
        $request->method('getStr2')->willReturn('adeus');
        
        // Use real factory
        $factory = new DefaultSequenceRuleFactory();
        
        // Create generator with factory
        $generator = new SequenceGenerator($factory);
        
        // Generate sequence
        $sequence = $generator->generate($request);
        
        // Assert sequence content
        $this->assertEquals([
            '1', '2', 'ola', '4', 'adeus', 'ola', '7', '8', 'ola', 'adeus', '11', 'ola', '13', '14', 'olaadeus'
        ], $sequence);
        
        // Make sure 15 is "olaadeus", not "olaadeusola" or "olaadeusadeus"
        $this->assertEquals('olaadeus', $sequence[14]);
    }
    
    public function testGenerateWithCustomRules(): void
    {
        // Create mock request
        $request = $this->createMock(FizzBuzzRequestInterface::class);
        $request->method('getStart')->willReturn(1);
        $request->method('getLimit')->willReturn(5);
        
        // Mock rule that always applies and returns "test"
        $rule1 = $this->createMock(SequenceRuleInterface::class);
        $rule1->method('appliesTo')->willReturn(true);
        $rule1->method('getReplacement')->willReturn('test');
        
        // Mock rule that also always applies but returns "shouldNotBeUsed"
        $rule2 = $this->createMock(SequenceRuleInterface::class);
        $rule2->method('appliesTo')->willReturn(true);
        $rule2->method('getReplacement')->willReturn('shouldNotBeUsed');
        
        // Create generator without factory
        $factory = $this->createMock(SequenceRuleFactoryInterface::class);
        $generator = new SequenceGenerator($factory);
        
        // Add rules manually
        $generator->addRule($rule1);
        $generator->addRule($rule2);
        
        // Generate sequence
        $sequence = $generator->generate($request);
        
        // Assert that only the first rule was applied (all values should be "test")
        $this->assertEquals(['test', 'test', 'test', 'test', 'test'], $sequence);
    }
} 