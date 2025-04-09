<?php

namespace App\Tests\Integration\MessageHandler;

use App\Entity\FizzBuzzRequest;
use App\Message\CreateFizzBuzzRequest;
use App\Repository\FizzBuzzRequestRepository; // Use the repository for finding
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateFizzBuzzRequestHandlerTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager;
    private ?MessageBusInterface $messageBus;
    private ?FizzBuzzRequestRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $kernel = self::bootKernel();
        $container = static::getContainer();

        $this->entityManager = $container->get('doctrine.orm.entity_manager');
        $this->messageBus = $container->get(MessageBusInterface::class);
        $this->repository = $container->get(FizzBuzzRequestRepository::class);

        // Ensure the schema is created for the test database
        $application = new Application($kernel);
        $application->setAutoExit(false);

        // Drop schema
        $inputDrop = new ArrayInput([
            'command' => 'doctrine:schema:drop',
            '--force' => true,
        ]);
        $application->run($inputDrop, new BufferedOutput());

        // Create schema
        $inputCreate = new ArrayInput([
            'command' => 'doctrine:schema:create',
        ]);
        $application->run($inputCreate, new BufferedOutput());
        
        // Clear table (using DELETE for compatibility)
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM fizz_buzz_requests');
        try {
             $connection->executeStatement('DELETE FROM sqlite_sequence WHERE name = \'fizz_buzz_requests\'');
         } catch (\Exception $e) {
             // Ignore if table doesn't exist or other issues
         }
    }

    public function testHandlerCreatesRequest(): void
    {
        // 1. Create the message
        $limit = 20;
        $div1 = 4;
        $div2 = 7;
        $str1 = 'four';
        $str2 = 'seven';
        $start = 1;

        $message = new CreateFizzBuzzRequest($limit, $div1, $div2, $str1, $str2, $start);

        // 2. Dispatch the message
        $this->messageBus->dispatch($message);

        // 3. Verify the result in the database
        // Note: Assumes synchronous processing or handler runs before assertion.
        // Might need adjustment (e.g., DoctrineTestUtils::assertCount) if async.
        
        $requestEntity = $this->repository->findOneBy([
            'limit' => $limit,
            'divisor1' => $div1,
            'divisor2' => $div2,
            'str1' => $str1,
            'str2' => $str2,
            'start' => $start,
        ]);

        $this->assertNotNull($requestEntity, 'Entity should be created by the handler.');
        $this->assertEquals(1, $requestEntity->getHits(), 'Hits should be initialized to 1 by repository logic.');
        $this->assertEquals('processed', $requestEntity->getTrackingState(), 'State should be marked as processed.');
        $this->assertNotNull($requestEntity->getProcessedAt(), 'ProcessedAt timestamp should be set.');
    }

    public function testHandlerIncrementsHits(): void
    {
        // 1. Manually create an initial request entity
        $limit = 25;
        $div1 = 5;
        $div2 = 6;
        $str1 = 'five';
        $str2 = 'six';
        $start = 1;

        $initialRequest = new FizzBuzzRequest($limit, $div1, $div2, $str1, $str2, $start);
        // Manually set initial state if needed, mimicking what *should* happen
        // $initialRequest->incrementHits(); 
        // $initialRequest->markAsProcessed(); 
        
        // Persist the initial entity
        $this->entityManager->persist($initialRequest);
        $this->entityManager->flush();
        $this->entityManager->clear(); // Detach to ensure we fetch fresh data later

        $initialId = $initialRequest->getId();
        $this->assertNotNull($initialId, 'Initial entity should have an ID after flush.');
        
        // 2. Create the message with the same parameters
        $message = new CreateFizzBuzzRequest($limit, $div1, $div2, $str1, $str2, $start);

        // 3. Dispatch the message
        $this->messageBus->dispatch($message);

        // 4. Verify the original entity in the database
        // Important: Re-fetch the entity after dispatch to get updated state
        $this->entityManager->clear(); // Clear EM cache before fetching again
        $requestEntity = $this->repository->find($initialId);
        
        $this->assertNotNull($requestEntity, 'Entity should still exist.');
        // This assertion should now PASS with the refactored handler
        $this->assertEquals(1, $requestEntity->getHits(), 'Hits should be incremented to 1.'); 
        $this->assertEquals('processed', $requestEntity->getTrackingState(), 'State should be marked as processed.');
        $this->assertNotNull($requestEntity->getProcessedAt(), 'ProcessedAt timestamp should be set.');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear table after test
        if ($this->entityManager !== null && $this->entityManager->isOpen()) {
           $connection = $this->entityManager->getConnection();
           try {
               $connection->executeStatement('DELETE FROM fizz_buzz_requests');
               $connection->executeStatement('DELETE FROM sqlite_sequence WHERE name = \'fizz_buzz_requests\'');
           } catch (\Exception $e) {
               // Ignore potential errors during teardown cleanup
           }
           $this->entityManager->close();
        }
        $this->entityManager = null; // avoid memory leaks
        $this->messageBus = null;
        $this->repository = null;
    }
} 