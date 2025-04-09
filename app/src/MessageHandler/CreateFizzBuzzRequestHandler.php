<?php

namespace App\MessageHandler;

use App\Dto\FizzBuzzRequestDto;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use App\Message\CreateFizzBuzzRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CreateFizzBuzzRequestHandler
{
    private FizzBuzzRequestRepositoryInterface $repository;
    private LoggerInterface $logger;

    public function __construct(
        FizzBuzzRequestRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
    }

    public function __invoke(CreateFizzBuzzRequest $message): void
    {
        try {
            $this->logger->info('Handling CreateFizzBuzzRequest message', ['message_details' => $message->toArray()]);

            $requestDto = new FizzBuzzRequestDto([
                'limit' => $message->getLimit(),
                'divisor1' => $message->getDivisor1(),
                'divisor2' => $message->getDivisor2(),
                'str1' => $message->getStr1(),
                'str2' => $message->getStr2(),
                'start' => $message->getStart(),
            ]);

            $requestEntity = $this->repository->findOrCreateRequest($requestDto);
            $this->repository->incrementHits($requestEntity);
            $this->repository->markAsProcessed($requestEntity);

            $this->logger->info('Successfully processed FizzBuzz request', ['request_id' => $requestEntity->getId(), 'new_hits' => $requestEntity->getHits()]);

        } catch (\Exception $e) {
            $this->logger->error('Error creating FizzBuzz request: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
                'message' => $message->toArray(),
            ]);
        }
    }
} 