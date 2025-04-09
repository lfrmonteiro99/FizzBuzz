<?php

namespace App\Service;

use App\Interface\FizzBuzzEventServiceInterface;
use App\Interface\FizzBuzzRequestInterface;
use App\Interface\FizzBuzzSequenceServiceInterface;
use App\Interface\FizzBuzzServiceInterface;
use App\Interface\SequenceCacheInterface;
use App\Message\CreateFizzBuzzRequest;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class FizzBuzzService implements FizzBuzzServiceInterface
{
    public function __construct(
        private readonly FizzBuzzSequenceServiceInterface $sequenceService,
        private readonly FizzBuzzEventServiceInterface $eventService,
        private readonly SequenceCacheInterface $cache,
        private readonly MessageBusInterface $messageBus,
        private readonly LoggerInterface $logger,
        private readonly Connection $connection
    ) {
    }

    public function generateSequence(FizzBuzzRequestInterface $request): array
    {
        $this->logger->info('Generating sequence', [
            'request' => [
                'start' => $request->getStart(),
                'limit' => $request->getLimit(),
                'divisor1' => $request->getDivisor1(),
                'divisor2' => $request->getDivisor2(),
                'str1' => $request->getStr1(),
                'str2' => $request->getStr2(),
            ]
        ]);

        // Try to get from cache first
        $cachedSequence = $this->cache->get($request);
        if ($cachedSequence !== null) {
            $this->logger->info('Found sequence in cache');
            
            // Save record directly to database
            //$this->saveRequestToDatabase($request);
            
            // Also dispatch message as a backup
            $this->dispatchMessage($request);
            
            return $cachedSequence;
        }

        try {
            $this->logger->info('Generating new sequence');
            // Generate sequence
            $sequence = $this->sequenceService->generateSequence($request);
            
            $this->logger->info('Caching sequence');
            // Cache the successful sequence
            $this->cache->set($request, $sequence);
            
            $this->logger->info('Dispatching event');
            // Dispatch event for the successful sequence
            $this->eventService->dispatchEvent($request, $sequence);
            
            $this->dispatchMessage($request);
            
            return $sequence;
        } catch (\Exception $e) {
            $this->logger->error('Error generating sequence', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Log the error but don't create a database record for failed requests
            // This ensures our database only contains records of successful requests
            throw $e;
        }
    }
    
    /**
     * Dispatch message for async processing.
     */
    private function dispatchMessage(FizzBuzzRequestInterface $request): void
    {
        try {
            $message = new CreateFizzBuzzRequest(
                (int)$request->getLimit(),
                (int)$request->getDivisor1(),
                (int)$request->getDivisor2(),
                (string)$request->getStr1(),
                (string)$request->getStr2(),
                (int)$request->getStart()
            );
            $this->messageBus->dispatch($message);
            $this->logger->info('Message dispatched for async processing');
        } catch (\Throwable $e) {
            $this->logger->error('Error dispatching message', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}