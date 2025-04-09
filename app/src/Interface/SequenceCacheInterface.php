<?php

namespace App\Interface;

use App\Interface\FizzBuzzRequestInterface;

interface SequenceCacheInterface
{
    /**
     * Get a cached sequence for the given request.
     *
     * @param FizzBuzzRequestInterface $request The FizzBuzz request
     * @return array|null The cached sequence or null if not found
     */
    public function get(FizzBuzzRequestInterface $request): ?array;

    /**
     * Cache a sequence for the given request.
     *
     * @param FizzBuzzRequestInterface $request The FizzBuzz request
     * @param array $sequence The sequence to cache
     */
    public function set(FizzBuzzRequestInterface $request, array $sequence): void;

    /**
     * Clear the cache.
     */
    public function clear(): void;
} 