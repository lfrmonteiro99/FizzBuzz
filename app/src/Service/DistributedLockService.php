<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Redis;

class DistributedLockService
{
    private const LOCK_TTL = 30; // 30 seconds
    private const RETRY_DELAY = 100; // 100ms
    private const MAX_RETRIES = 10;

    public function __construct(
        private readonly Redis $redis,
        private readonly LoggerInterface $logger
    ) {
    }

    public function acquireLock(string $key): bool
    {
        $retryCount = 0;
        while ($retryCount < self::MAX_RETRIES) {
            if ($this->redis->set($key, 1, ['NX', 'EX' => self::LOCK_TTL])) {
                return true;
            }
            
            usleep(self::RETRY_DELAY * 1000);
            $retryCount++;
        }

        $this->logger->warning('Failed to acquire lock after {retries} attempts', [
            'retries' => self::MAX_RETRIES,
            'key' => $key
        ]);

        return false;
    }

    public function releaseLock(string $key): void
    {
        $this->redis->del($key);
    }
} 