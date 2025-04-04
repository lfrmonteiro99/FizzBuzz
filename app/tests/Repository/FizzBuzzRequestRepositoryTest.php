<?php

namespace App\Tests\Repository;

use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use App\Repository\FizzBuzzRequestRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\Tools\SchemaTool;

class FizzBuzzRequestRepositoryTest extends KernelTestCase
{
    private $entityManager;
    private $repository;
    private $schemaTool;
    private $classes;

    protected function setUp(): void
    {
        // Boot the Symfony kernel
        self::bootKernel();
        
        // Get the entity manager from the service container
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        
        // Get the repository from the entity manager
        $this->repository = self::getContainer()->get(FizzBuzzRequestRepositoryInterface::class);
        
        // Create schema for testing
        $this->schemaTool = new SchemaTool($this->entityManager);
        $this->classes = [$this->entityManager->getClassMetadata(FizzBuzzRequest::class)];
        
        // Drop and create schema
        $this->schemaTool->dropSchema($this->classes);
        $this->schemaTool->createSchema($this->classes);
    }

    public function testFindMostFrequentRequestWithNoRequests(): void
    {
        // When no requests exist, findMostFrequentRequest should return null
        $result = $this->repository->findMostFrequentRequest();
        
        $this->assertNull($result);
    }
    
    public function testFindMostFrequentRequestWithMultipleRequests(): void
    {
        // Create test requests with different hit counts
        $request1 = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $request2 = new FizzBuzzRequest(10, 2, 4, 'Even', 'Four');
        
        // Add requests to database
        $this->entityManager->persist($request1);
        $this->entityManager->persist($request2);
        $this->entityManager->flush();
        
        // Increment hits for request2 to make it more frequent
        $request2->incrementHits();
        $request2->incrementHits();
        $this->entityManager->flush();
        
        // Call the method
        $result = $this->repository->findMostFrequentRequest();
        
        // The most frequent request should be request2
        $this->assertSame($request2->getLimit(), $result->getLimit());
        $this->assertSame($request2->getInt1(), $result->getInt1());
        $this->assertSame($request2->getInt2(), $result->getInt2());
        $this->assertSame($request2->getStr1(), $result->getStr1());
        $this->assertSame($request2->getStr2(), $result->getStr2());
        $this->assertSame(3, $result->getHits());
    }
    
    public function testFindOrCreateRequestForNewRequest(): void
    {
        // Call findOrCreateRequest with parameters that don't exist in the database yet
        $result = $this->repository->findOrCreateRequest(15, 3, 5, 'Fizz', 'Buzz');
        
        // Check that a new request was created
        $this->assertInstanceOf(FizzBuzzRequest::class, $result);
        $this->assertEquals(15, $result->getLimit());
        $this->assertEquals(3, $result->getInt1());
        $this->assertEquals(5, $result->getInt2());
        $this->assertEquals('Fizz', $result->getStr1());
        $this->assertEquals('Buzz', $result->getStr2());
        $this->assertEquals(1, $result->getHits());
        
        // The entity should be stored in the database
        $storedRequest = $this->repository->findOneBy([
            'limit' => 15,
            'int1' => 3,
            'int2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz',
        ]);
        
        $this->assertNotNull($storedRequest);
        $this->assertEquals(1, $storedRequest->getHits());
    }
    
    public function testFindOrCreateRequestForExistingRequest(): void
    {
        // Create a request in the database
        $existingRequest = new FizzBuzzRequest(10, 2, 4, 'Even', 'Four');
        $this->entityManager->persist($existingRequest);
        $this->entityManager->flush();
        
        // Initial hits should be 1
        $this->assertEquals(1, $existingRequest->getHits());
        
        // Call findOrCreateRequest with the same parameters
        $result = $this->repository->findOrCreateRequest(10, 2, 4, 'Even', 'Four');
        
        // The result should be the same request with incremented hits
        $this->assertEquals(10, $result->getLimit());
        $this->assertEquals(2, $result->getInt1());
        $this->assertEquals(4, $result->getInt2());
        $this->assertEquals('Even', $result->getStr1());
        $this->assertEquals('Four', $result->getStr2());
        $this->assertEquals(2, $result->getHits());
    }
    
    protected function tearDown(): void
    {
        // Drop the schema
        if ($this->schemaTool) {
            $this->schemaTool->dropSchema($this->classes);
        }
        
        // Close the entity manager
        if ($this->entityManager) {
            $this->entityManager->close();
            $this->entityManager = null;
        }
        
        parent::tearDown();
    }
} 