<?php

namespace App\Tests\Functional\Controller;

use App\Event\ValidationFailedEvent;
use App\Interface\FizzBuzzEventServiceInterface;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\FizzBuzzRequestValidatorInterface;
use App\Interface\FizzBuzzSequenceServiceInterface;
use App\Interface\FizzBuzzServiceInterface;
use App\Interface\SequenceCacheInterface;
use App\Message\CreateFizzBuzzRequest;
use App\Service\FizzBuzzRequestValidator;
use App\Service\FizzBuzzService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class FizzBuzzControllerTest extends WebTestCase
{
    private $client;
    private $cacheMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();

        // Mock only the Sequence Cache globally
        $this->cacheMock = $this->createMock(SequenceCacheInterface::class);
        static::getContainer()->set(SequenceCacheInterface::class, $this->cacheMock);
    }

    public function testFizzBuzzEndpoint(): void
    {
        // Use the client from setUp
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('sequence', $responseData);
        $this->assertArrayHasKey('count', $responseData);
        
        $this->assertEquals(15, $responseData['count']);
        $this->assertEquals([
            '1', '2', 'fizz', '4', 'buzz', 'fizz', '7', '8', 'fizz', 'buzz',
            '11', 'fizz', '13', '14', 'fizzbuzz'
        ], $responseData['sequence']);
    }
    
    public function testFizzBuzzEndpointCacheHit(): void
    {
        $client = $this->client;
        $generatedSequence = null; // Variable to store the generated sequence
        $params = [
            'divisor1' => '4',
            'divisor2' => '6',
            'limit' => '6', // Keep limit small for sample sequence
            'str1' => 'quad',
            'str2' => 'hexa'
        ];

        // Configure the mock interactions
        $this->cacheMock->method('get')
            ->with($this->isInstanceOf(\App\Interface\FizzBuzzRequestInterface::class))
            ->willReturnCallback(function () use (&$generatedSequence) {
                // Return null first time, then the captured sequence
                $returnValue = $generatedSequence; // Will be null on first call
                return $returnValue;
            });

        // Expect 'set' to be called exactly ONCE after the first miss
        // Capture the sequence passed to 'set'
        $this->cacheMock->expects($this->once())
            ->method('set')
            ->with(
                $this->isInstanceOf(\App\Interface\FizzBuzzRequestInterface::class),
                $this->isType('array') // Expecting the generated sequence array
            )
            ->willReturnCallback(function ($request, $sequence) use (&$generatedSequence) {
                $generatedSequence = $sequence; // Capture the sequence
            });

        // First request (populates cache & captures sequence via mock)
        $client->request('GET', '/fizzbuzz', $params);
        $this->assertResponseIsSuccessful();

        // Second request (should hit cache)
        $client->request('GET', '/fizzbuzz', $params);
        $this->assertResponseIsSuccessful();

        // Verify response is the same
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        // Assert the sequence returned by the second request matches the one generated and captured
        $this->assertNotNull($generatedSequence, 'Sequence should have been captured from cache set');
        $this->assertEquals($generatedSequence, $responseData['sequence']);
    }
    
    public function testFizzBuzzEndpointWithCustomParameters(): void
    {
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '2',
            'divisor2' => '7',
            'limit' => '10',
            'str1' => 'even',
            'str2' => 'seven'
        ]);
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(10, $responseData['count']);
        $this->assertEquals([
            '1', 'even', '3', 'even', '5', 'even', 'seven', 'even', '9', 'even'
        ], $responseData['sequence']);
    }
    
    public function testFizzBuzzEndpointWithMissingParameters(): void
    {
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            // Missing divisor2
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }
    
    public function testFizzBuzzEndpointWithInvalidParameters(): void
    {
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '0', // Invalid - must be positive
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }
    
    public function testFizzBuzzEndpointWithSameDivisors(): void
    {
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '5',
            'divisor2' => '5', // Same as divisor1, which is invalid
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }
    
    public function testFizzBuzzEndpointWithLargeLimit(): void
    {
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '1001', // Above maximum limit of 1000
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }
    
    public function testFizzBuzzEndpointWithLargeDivisor(): void
    {
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '101', // Above maximum divisor of 100
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }
    
    public function testFizzBuzzEndpointWithCustomStart(): void
    {
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '5'
        ]);
        
        $this->assertResponseIsSuccessful();
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals(1, $responseData['count']);
        $this->assertEquals([
            'buzz'
        ], $responseData['sequence']);
    }
    
    public function testFizzBuzzEndpointWithNegativeParameters(): void
    {
        $client = $this->client;
        
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '-5', // Negative limit is invalid
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testFizzBuzzEndpointMessageDispatchOnCacheMiss(): void
    {
        $client = $this->client;
        $container = static::getContainer();
        $params = ['divisor1' => '1', 'divisor2' => '2', 'limit' => '1', 'str1' => 'a', 'str2' => 'b'];

        // --- Local Mocking Setup ---
        // 1. Get real dependencies (except cache, which is already mocked globally)
        $realSequenceService = $container->get(FizzBuzzSequenceServiceInterface::class);
        $realEventService = $container->get(FizzBuzzEventServiceInterface::class);
        $realLogger = $container->get(LoggerInterface::class); // Or use test logger if preferred
        // Assuming Connection isn't strictly needed for this path or can be obtained
        $realConnection = $container->get('doctrine.dbal.default_connection'); 

        // 2. Create local mock for MessageBusInterface
        $localMessageBusMock = $this->createMock(MessageBusInterface::class);

        // 3. Manually create FizzBuzzService with the mix
        $fizzBuzzServiceWithMockBus = new FizzBuzzService(
            $realSequenceService,
            $realEventService, 
            $this->cacheMock, // Use the global cache mock
            $localMessageBusMock, // Use the local bus mock
            $realLogger,
            $realConnection
        );

        // 4. Temporarily replace the service in the container for this test
        $container->set(FizzBuzzServiceInterface::class, $fizzBuzzServiceWithMockBus);
        // --- End Local Mocking Setup ---

        // Simulate cache miss using the global mock
        $this->cacheMock->method('get')->willReturn(null);
        $this->cacheMock->method('set'); // Expect set on miss

        // Expect dispatch to be called because sequence was generated
        $localMessageBusMock->expects($this->atLeastOnce()) // Use atLeastOnce as other logs might dispatch
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($params) {
                return $message instanceof CreateFizzBuzzRequest &&
                       $message->getLimit() === (int)$params['limit'] &&
                       $message->getDivisor1() === (int)$params['divisor1'] &&
                       $message->getDivisor2() === (int)$params['divisor2'] &&
                       $message->getStr1() === $params['str1'] &&
                       $message->getStr2() === $params['str2'];
            })); // No ->willReturn needed

        // Make the request which will now use our temporary service instance
        $client->request('GET', '/fizzbuzz', $params);
        $this->assertResponseIsSuccessful();

        // Restore original service? Not strictly necessary if kernel reboots between tests.
    }

    public function testFizzBuzzEndpointMessageDispatchOnCacheHit(): void
    {
        $client = $this->client;
        $container = static::getContainer();
        $testSequence = ['hit'];
        $params = ['divisor1' => '1', 'divisor2' => '2', 'limit' => '1', 'str1' => 'a', 'str2' => 'b'];

        // --- Local Mocking Setup ---
        $realSequenceService = $container->get(FizzBuzzSequenceServiceInterface::class);
        $realEventService = $container->get(FizzBuzzEventServiceInterface::class);
        $realLogger = $container->get(LoggerInterface::class);
        $realConnection = $container->get('doctrine.dbal.default_connection');
        $localMessageBusMock = $this->createMock(MessageBusInterface::class);

        $fizzBuzzServiceWithMockBus = new FizzBuzzService(
            $realSequenceService, $realEventService, $this->cacheMock, $localMessageBusMock, $realLogger, $realConnection
        );
        $container->set(FizzBuzzServiceInterface::class, $fizzBuzzServiceWithMockBus);
        // --- End Local Mocking Setup ---

        // Simulate cache hit using the global mock
        $this->cacheMock->method('get')->willReturn($testSequence);

        // Expect dispatch to be called even on cache hit
        $localMessageBusMock->expects($this->atLeastOnce())
            ->method('dispatch')
            ->with($this->callback(function ($message) use ($params) {
                return $message instanceof CreateFizzBuzzRequest &&
                       $message->getLimit() === (int)$params['limit'] &&
                       $message->getDivisor1() === (int)$params['divisor1'] &&
                       $message->getDivisor2() === (int)$params['divisor2'] &&
                       $message->getStr1() === $params['str1'] &&
                       $message->getStr2() === $params['str2'];
            }));

        $client->request('GET', '/fizzbuzz', $params);
        $this->assertResponseIsSuccessful();
    }

    public function testFizzBuzzEndpointEventDispatchOnCacheMiss(): void
    {
        $client = $this->client;
        $container = static::getContainer();
        $params = ['divisor1' => '3', 'divisor2' => '5', 'limit' => '2', 'str1' => 'f', 'str2' => 'z'];

        // --- Local Mocking Setup ---
        $realSequenceService = $container->get(FizzBuzzSequenceServiceInterface::class);
        $realMessageBus = $container->get(MessageBusInterface::class);
        $realLogger = $container->get(LoggerInterface::class);
        $realConnection = $container->get('doctrine.dbal.default_connection');
        $localEventServiceMock = $this->createMock(FizzBuzzEventServiceInterface::class);

        $fizzBuzzServiceWithMockEvent = new FizzBuzzService(
            $realSequenceService, $localEventServiceMock, $this->cacheMock, $realMessageBus, $realLogger, $realConnection
        );
        $container->set(FizzBuzzServiceInterface::class, $fizzBuzzServiceWithMockEvent);
        // --- End Local Mocking Setup ---

        // Simulate cache miss
        $this->cacheMock->method('get')->willReturn(null);
        $this->cacheMock->method('set');

        // Expect event dispatch to be called ONLY on cache miss
        $localEventServiceMock->expects($this->once())
            ->method('dispatchEvent')
            ->with(
                $this->isInstanceOf(\App\Interface\FizzBuzzRequestInterface::class),
                $this->isType('array')
            );

        $client->request('GET', '/fizzbuzz', $params);
        $this->assertResponseIsSuccessful();
    }

    public function testFizzBuzzEndpointNoEventDispatchOnCacheHit(): void
    {
        $client = $this->client;
        $container = static::getContainer();
        $testSequence = ['hit', 'again'];
        $params = ['divisor1' => '7', 'divisor2' => '11', 'limit' => '2', 'str1' => 's', 'str2' => 'e'];

        // --- Local Mocking Setup ---
        $realSequenceService = $container->get(FizzBuzzSequenceServiceInterface::class);
        $realMessageBus = $container->get(MessageBusInterface::class);
        $realLogger = $container->get(LoggerInterface::class);
        $realConnection = $container->get('doctrine.dbal.default_connection');
        $localEventServiceMock = $this->createMock(FizzBuzzEventServiceInterface::class);

        $fizzBuzzServiceWithMockEvent = new FizzBuzzService(
            $realSequenceService, $localEventServiceMock, $this->cacheMock, $realMessageBus, $realLogger, $realConnection
        );
        $container->set(FizzBuzzServiceInterface::class, $fizzBuzzServiceWithMockEvent);
        // --- End Local Mocking Setup ---

        // Simulate cache hit
        $this->cacheMock->method('get')->willReturn($testSequence);

        // Expect event dispatch NOT to be called
        $localEventServiceMock->expects($this->never())
            ->method('dispatchEvent');

        $client->request('GET', '/fizzbuzz', $params);
        $this->assertResponseIsSuccessful();
    }

    public function testFizzBuzzEndpointWithZeroStart(): void
    {
        $client = $this->client;
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '0' // Invalid start
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        // Assert specific error message part
        $this->assertStringContainsString('start:', $client->getResponse()->getContent());
        $this->assertStringContainsString('must be a positive number', $client->getResponse()->getContent());
    }

    public function testFizzBuzzEndpointWithZeroDivisor2(): void
    {
        $client = $this->client;
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '0', // Invalid divisor2
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('divisor2:', $client->getResponse()->getContent());
        $this->assertStringContainsString('must be a positive number', $client->getResponse()->getContent());
    }

    public function testFizzBuzzEndpointWithLargeDivisor2(): void
    {
        $client = $this->client;
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '5',
            'divisor2' => '101', // Invalid divisor2 (>100)
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => 'buzz'
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('divisor2:', $client->getResponse()->getContent());
        $this->assertStringContainsString('cannot be greater than 100', $client->getResponse()->getContent());
    }
    
    public function testFizzBuzzEndpointWithStartGreaterThanLimit(): void
    {
        $client = $this->client;
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '10' // Invalid start > limit
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('start value must not exceed the limit', $client->getResponse()->getContent());
    }

    public function testFizzBuzzEndpointWithEmptyStr1(): void
    {
        $client = $this->client;
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => '', // Invalid empty str1
            'str2' => 'buzz'
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('str1:', $client->getResponse()->getContent());
        $this->assertStringContainsString('cannot be empty', $client->getResponse()->getContent());
    }

    public function testFizzBuzzEndpointWithEmptyStr2(): void
    {
        $client = $this->client;
        $client->request('GET', '/fizzbuzz', [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '15',
            'str1' => 'fizz',
            'str2' => '' // Invalid empty str2
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $responseData = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContainsString('str2:', $client->getResponse()->getContent());
        $this->assertStringContainsString('cannot be empty', $client->getResponse()->getContent());
    }

    public function testValidationFailedEventDispatched(): void
    {
        $client = $this->client;
        $container = static::getContainer();
        $params = [
            'divisor1' => '3',
            'divisor2' => '5',
            'limit' => '5',
            'str1' => 'fizz',
            'str2' => 'buzz',
            'start' => '10' // Fails start <= limit check in custom validator
        ];

        // --- Local Mocking Setup for Event Dispatcher ---
        // 1. Get real dependencies for FizzBuzzRequestValidator
        $realSymfonyValidator = $container->get(ValidatorInterface::class);
        $realLogger = $container->get(LoggerInterface::class);

        // 2. Create local mock for EventDispatcherInterface
        $localEventDispatcherMock = $this->createMock(EventDispatcherInterface::class);

        // 3. Manually create FizzBuzzRequestValidator with the mix
        $validatorWithMockDispatcher = new FizzBuzzRequestValidator(
            $realSymfonyValidator,
            $realLogger, 
            $localEventDispatcherMock // Use the local dispatcher mock
        );

        // 4. Temporarily replace the service in the container for this test
        // Ensure you use the correct service ID used in services.yaml or autowiring
        // It might be the interface or the concrete class name
        $container->set(FizzBuzzRequestValidatorInterface::class, $validatorWithMockDispatcher);
        // --- End Local Mocking Setup ---

        // Expect the event dispatcher's dispatch method to be called
        $localEventDispatcherMock->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(ValidationFailedEvent::class), // Check event type
                ValidationFailedEvent::EVENT_NAME // Check event name
            );

        // Make the request with invalid parameters
        $client->request('GET', '/fizzbuzz', $params);

        // Assert the response is bad request (validation should fail)
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        
        // We don't need to assert the error message content here, 
        // the main goal is asserting the event dispatch expectation.
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Avoid memory leaks
        $this->client = null;
        $this->cacheMock = null;
    }
} 