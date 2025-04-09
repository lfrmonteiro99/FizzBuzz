<?php

namespace App\Tests\Repository;

use App\Dto\FizzBuzzRequestDto;
use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use App\Repository\FizzBuzzRequestRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\OptimisticLockException;

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
        $this->assertSame($request2->getDivisor1(), $result->getDivisor1());
        $this->assertSame($request2->getDivisor2(), $result->getDivisor2());
        $this->assertSame($request2->getStr1(), $result->getStr1());
        $this->assertSame($request2->getStr2(), $result->getStr2());
        $this->assertSame(3, $result->getHits());
    }
    
    public function testFindOrCreateRequestWithDtoForNewRequest(): void
    {
        // Create a DTO with parameters that don't exist in the database yet
        $dto = new FizzBuzzRequestDto([
            'start' => 1,
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz',
        ]);
        
        // Call findOrCreateRequest with the DTO
        $result = $this->repository->findOrCreateRequest($dto);
        
        // Check that a new request was created
        $this->assertInstanceOf(FizzBuzzRequest::class, $result);
        $this->assertEquals(15, $result->getLimit());
        $this->assertEquals(3, $result->getDivisor1());
        $this->assertEquals(5, $result->getDivisor2());
        $this->assertEquals('Fizz', $result->getStr1());
        $this->assertEquals('Buzz', $result->getStr2());
        $this->assertEquals(1, $result->getStart());
        
        // Save the entity to database to test persisting logic
        $this->entityManager->persist($result);
        $this->entityManager->flush();
        
        // The entity should be stored in the database
        $storedRequest = $this->repository->findOneBy([
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5,
            'str1' => 'Fizz',
            'str2' => 'Buzz',
        ]);
        
        $this->assertNotNull($storedRequest);
    }
    
    public function testFindOrCreateRequestWithDtoForExistingRequest(): void
    {
        // Create a request in the database
        $existingRequest = new FizzBuzzRequest(10, 2, 4, 'Even', 'Four', 1);
        $this->entityManager->persist($existingRequest);
        $this->entityManager->flush();
        
        // Create a DTO with the same parameters
        $dto = new FizzBuzzRequestDto([
            'start' => 1,
            'limit' => 10,
            'divisor1' => 2,
            'divisor2' => 4,
            'str1' => 'Even',
            'str2' => 'Four',
        ]);
        
        // Call findOrCreateRequest with the DTO
        $result = $this->repository->findOrCreateRequest($dto);
        
        // The result should be the existing request
        $this->assertEquals(10, $result->getLimit());
        $this->assertEquals(2, $result->getDivisor1());
        $this->assertEquals(4, $result->getDivisor2());
        $this->assertEquals('Even', $result->getStr1());
        $this->assertEquals('Four', $result->getStr2());
    }
    
    public function testIncrementHits(): void
    {
        // Create a request
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $this->entityManager->persist($request);
        $this->entityManager->flush();
        
        // Initial hits should be 1
        $this->assertEquals(1, $request->getHits());
        
        // Call incrementHits
        $this->repository->incrementHits($request);
        
        // Refresh entity to get updated values
        $this->entityManager->refresh($request);
        
        // Hits should be incremented to 2
        $this->assertEquals(2, $request->getHits());
    }
    
    public function testMarkAsProcessed(): void
    {
        // Create a request
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $this->entityManager->persist($request);
        $this->entityManager->flush();
        
        // Initial tracking state should be 'pending'
        $this->assertEquals('pending', $request->getTrackingState());
        
        // Call markAsProcessed
        $this->repository->markAsProcessed($request);
        
        // Refresh entity to get updated values
        $this->entityManager->refresh($request);
        
        // Tracking state should be 'processed'
        $this->assertEquals('processed', $request->getTrackingState());
    }
    
    public function testMarkAsFailed(): void
    {
        // Create a request
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $this->entityManager->persist($request);
        $this->entityManager->flush();
        
        // Initial tracking state should be 'pending'
        $this->assertEquals('pending', $request->getTrackingState());
        
        // Call markAsFailed
        $this->repository->markAsFailed($request);
        
        // Refresh entity to get updated values
        $this->entityManager->refresh($request);
        
        // Tracking state should be 'failed'
        $this->assertEquals('failed', $request->getTrackingState());
    }
    
    public function testGetMostFrequentRequest(): void
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
        $result = $this->repository->getMostFrequentRequest();
        
        // The most frequent request should be request2
        $this->assertSame($request2->getLimit(), $result->getLimit());
        $this->assertSame($request2->getDivisor1(), $result->getDivisor1());
        $this->assertSame($request2->getDivisor2(), $result->getDivisor2());
        $this->assertSame($request2->getStr1(), $result->getStr1());
        $this->assertSame($request2->getStr2(), $result->getStr2());
        $this->assertSame(3, $result->getHits());
    }
    
    public function testIncrementHitsWithLock(): void
    {
        // Create a request
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $this->entityManager->persist($request);
        $this->entityManager->flush();
        
        // Initial hits should be 1
        $this->assertEquals(1, $request->getHits());
        
        // Call incrementHitsWithLock
        $this->repository->incrementHitsWithLock($request);
        
        // Refresh entity to get updated values
        $this->entityManager->refresh($request);
        
        // Hits should be incremented to 2
        $this->assertEquals(2, $request->getHits());
    }
    
    public function testFindPendingRequests(): void
    {
        // Create a request with the default 'pending' status and a creation date in the past
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        $requestCreatedAt = new \ReflectionProperty($request, 'createdAt');
        $requestCreatedAt->setAccessible(true);
        $requestCreatedAt->setValue($request, new \DateTimeImmutable('-10 minutes'));
        
        // Create another request that is also pending but created recently
        $recentRequest = new FizzBuzzRequest(10, 2, 4, 'Even', 'Four');
        
        // Create a request that is already processed
        $processedRequest = new FizzBuzzRequest(20, 2, 5, 'Two', 'Five');
        $processedRequest->markAsProcessed();
        
        // Add all requests to the database
        $this->entityManager->persist($request);
        $this->entityManager->persist($recentRequest);
        $this->entityManager->persist($processedRequest);
        $this->entityManager->flush();
        
        // Call findPendingRequests
        $pendingRequests = $this->repository->findPendingRequests();
        
        // Only the old pending request should be returned
        $this->assertCount(1, $pendingRequests);
        $this->assertEquals(15, $pendingRequests[0]->getLimit());
        $this->assertEquals(3, $pendingRequests[0]->getDivisor1());
        $this->assertEquals(5, $pendingRequests[0]->getDivisor2());
    }
    
    public function testSaveWithoutFlush(): void
    {
        // Create a new request
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        
        // Call save with flush = false
        $this->repository->save($request, false);
        
        // The request should be persisted but not yet in the database
        // We can check this by trying to find it - it should not be found
        $result = $this->repository->findOneBy([
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5
        ]);
        
        $this->assertNull($result);
        
        // Now flush the entity manager
        $this->entityManager->flush();
        
        // The request should now be in the database
        $result = $this->repository->findOneBy([
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5
        ]);
        
        $this->assertNotNull($result);
    }
    
    public function testSaveWithFlush(): void
    {
        // Create a new request
        $request = new FizzBuzzRequest(15, 3, 5, 'Fizz', 'Buzz');
        
        // Call save with flush = true
        $this->repository->save($request, true);
        
        // The request should be persisted and immediately in the database
        $result = $this->repository->findOneBy([
            'limit' => 15,
            'divisor1' => 3,
            'divisor2' => 5
        ]);
        
        $this->assertNotNull($result);
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