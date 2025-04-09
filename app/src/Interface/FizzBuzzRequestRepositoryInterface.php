<?php

namespace App\Interface;

use App\Dto\FizzBuzzRequestDto;
use App\Entity\FizzBuzzRequest;

/**
 * Interface for FizzBuzzRequest repository operations
 */
interface FizzBuzzRequestRepositoryInterface
{
    /**
     * Find the most frequently requested FizzBuzz sequence
     *
     * @return FizzBuzzRequest|null The most frequent request or null if none exists
     */
    public function findMostFrequentRequest(): ?FizzBuzzRequest;

    /**
     * Find an existing request with the given parameters or create a new one
     *
     * @param FizzBuzzRequestDto $dto The DTO containing the request parameters
     * @return FizzBuzzRequest The request entity
     */
    public function findOrCreateRequest(FizzBuzzRequestDto $dto): FizzBuzzRequest;

    /**
     * Increment the hit count for a request
     *
     * @param FizzBuzzRequest $request The request to increment hits for
     * @return void
     */
    public function incrementHits(FizzBuzzRequest $request): void;

    /**
     * Mark a request as processed
     *
     * @param FizzBuzzRequest $request The request to mark as processed
     * @return void
     */
    public function markAsProcessed(FizzBuzzRequest $request): void;

    /**
     * Mark a request as failed
     *
     * @param FizzBuzzRequest $request The request to mark as failed
     * @return void
     */
    public function markAsFailed(FizzBuzzRequest $request): void;

    /**
     * Save a FizzBuzzRequest entity
     *
     * @param FizzBuzzRequest $request The request to save
     * @param bool $flush Whether to flush the entity manager
     * @return void
     */
    public function save(FizzBuzzRequest $request, bool $flush = false): void;
} 