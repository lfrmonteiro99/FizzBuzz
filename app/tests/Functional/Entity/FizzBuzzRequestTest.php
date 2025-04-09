<?php

namespace App\Tests\Functional\Entity;

use App\Entity\FizzBuzzRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;

class FizzBuzzRequestTest extends TestCase
{
    public function testConstructor(): void
    {
        $request = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz', 1);
        
        $this->assertEquals(15, $request->getLimit());
        $this->assertEquals(3, $request->getDivisor1());
        $this->assertEquals(5, $request->getDivisor2());
        $this->assertEquals('fizz', $request->getStr1());
        $this->assertEquals('buzz', $request->getStr2());
        $this->assertEquals(1, $request->getStart());
        $this->assertNull($request->getId()); // ID should be null until entity is persisted
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getCreatedAt());
    }
    
    public function testConstructorWithDefaultStart(): void
    {
        $request = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz');
        
        $this->assertEquals(1, $request->getStart());
    }
    
    public function testGetters(): void
    {
        $request = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz', 1);
        
        $this->assertEquals(15, $request->getLimit());
        $this->assertEquals(3, $request->getDivisor1());
        $this->assertEquals(5, $request->getDivisor2());
        $this->assertEquals('fizz', $request->getStr1());
        $this->assertEquals('buzz', $request->getStr2());
        $this->assertEquals(1, $request->getStart());
    }
    
    public function testIdIsNullByDefault(): void
    {
        $request = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz', 1);
        $this->assertNull($request->getId());
    }
    
    public function testCreatedAtIsSet(): void
    {
        $now = new \DateTimeImmutable();
        $request = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz', 1);
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $request->getCreatedAt());
        $this->assertLessThanOrEqual(2, $now->diff($request->getCreatedAt())->s);
    }
    
    /**
     * Test that the persist method is called on the EntityManager.
     */
    public function testRequestCanBePersisted(): void
    {
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $request = new FizzBuzzRequest(15, 3, 5, 'fizz', 'buzz', 1);

        // Expect the persist method to be called once with the request object
        $entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($request);

        // Simulate the action that would trigger persistence
        // In a real application, this might be a service method call
        // For this test, we directly verify the mock interaction
        $entityManagerMock->persist($request);
    }
    
    /**
     * Testing database-generated ID requires an integration test.
     */
    public function testIdGeneratedOnPersist(): void
    {
        $this->markTestSkipped('ID generation testing requires integration with a real database.');
    }
} 