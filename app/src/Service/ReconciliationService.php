<?php

namespace App\Service;

use App\Repository\FizzBuzzRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReconciliationService
{
    public function __construct(
        private readonly FizzBuzzRequestRepository $repository,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger
    ) {
    }

    public function reconcilePendingRequests(): void
    {
        $pendingRequests = $this->repository->findPendingRequests();

        foreach ($pendingRequests as $request) {
            try {
                $this->em->wrapInTransaction(function() use ($request) {
                    $request->incrementHits();
                    $request->markAsProcessed();
                });
            } catch (\Exception $e) {
                $this->logger->error('Failed to reconcile request', [
                    'requestId' => $request->getId(),
                    'error' => $e->getMessage()
                ]);
                $request->markAsFailed();
            }
        }

        $this->em->flush();
    }
} 