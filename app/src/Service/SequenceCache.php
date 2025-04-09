<?php

namespace App\Service;

use App\Interface\FizzBuzzRequestInterface;
use App\Interface\SequenceCacheInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class SequenceCache implements SequenceCacheInterface
{
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    public function get(FizzBuzzRequestInterface $request): ?array
    {
        $key = $this->generateCacheKey($request);
        $this->logger->info('Getting sequence from cache', ['key' => $key]);
        
        try {
            $result = $this->cache->get($key, function (ItemInterface $item) {
                $item->expiresAfter(self::CACHE_TTL);
                $this->logger->info('Cache miss');
                return null;
            });
            
            if ($result !== null) {
                $this->logger->info('Cache hit');
            }
            
            return $result;
        } catch (\Exception $e) {
            $this->logger->error('Error getting from cache', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    public function set(FizzBuzzRequestInterface $request, array $sequence): void
    {
        $key = $this->generateCacheKey($request);
        $this->logger->info('Setting sequence in cache', ['key' => $key]);
        
        try {
            $this->cache->delete($key); // Ensure we're replacing any existing item
            $this->cache->get($key, function (ItemInterface $item) use ($sequence) {
                $item->expiresAfter(self::CACHE_TTL);
                $this->logger->info('Sequence cached successfully');
                return $sequence;
            });
        } catch (\Exception $e) {
            $this->logger->error('Error setting cache', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function clear(FizzBuzzRequestInterface $request = null): void
    {
        if ($request) {
            $key = $this->generateCacheKey($request);
            $this->logger->info('Clearing specific cache key', ['key' => $key]);
            try {
                $this->cache->delete($key);
            } catch (\Exception $e) {
                $this->logger->error('Error clearing specific cache key', [
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            $this->logger->info('Cache clearing is not supported for the full cache');
        }
    }

    private function generateCacheKey(FizzBuzzRequestInterface $request): string
    {
        $requestParams = [
            'start' => $request->getStart(),
            'limit' => $request->getLimit(),
            'divisor1' => $request->getDivisor1(),
            'divisor2' => $request->getDivisor2(),
            'str1' => $request->getStr1(),
            'str2' => $request->getStr2()
        ];
        
        return md5(serialize($requestParams));
    }
} 