<?php

namespace App\Repository;

use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FizzBuzzRequest>
 */
class FizzBuzzRequestRepository extends ServiceEntityRepository implements FizzBuzzRequestRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FizzBuzzRequest::class);
    }

    /**
     * {@inheritdoc}
     */
    public function findMostFrequentRequest(): ?FizzBuzzRequest
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->orderBy('r.hits', 'DESC')
            ->setMaxResults(1);
            
        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    /**
     * Find an existing request with the given parameters or create a new one
     *
     * @param int $limit The upper limit
     * @param int $int1 The first divisor
     * @param int $int2 The second divisor
     * @param string $str1 The string for numbers divisible by int1
     * @param string $str2 The string for numbers divisible by int2
     * 
     * @return FizzBuzzRequest The request entity
     * @throws \Doctrine\ORM\ORMException If there's an error during database operations
     */
    public function findOrCreateRequest(int $limit, int $int1, int $int2, string $str1, string $str2): FizzBuzzRequest
    {
        try {
            // Begin transaction for data consistency
            $this->getEntityManager()->beginTransaction();
            
            // Use DQL with parameter binding to avoid SQL injection and handle reserved words
            $dql = "SELECT r FROM App\Entity\FizzBuzzRequest r 
                    WHERE r.limit = :limit 
                    AND r.int1 = :int1 
                    AND r.int2 = :int2 
                    AND r.str1 = :str1 
                    AND r.str2 = :str2";
            
            $query = $this->getEntityManager()->createQuery($dql);
            $query->setParameters([
                'limit' => $limit,
                'int1' => $int1,
                'int2' => $int2,
                'str1' => $str1,
                'str2' => $str2,
            ]);
            
            $existingRequest = $query->getOneOrNullResult();
            
            if ($existingRequest) {
                // If found, increment the hit counter
                $existingRequest->incrementHits();
                $this->getEntityManager()->flush();
                $this->getEntityManager()->commit();
                return $existingRequest;
            }
            
            // If not found, create a new request
            $request = new FizzBuzzRequest($limit, $int1, $int2, $str1, $str2);
            $this->getEntityManager()->persist($request);
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
            
            return $request;
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            if ($this->getEntityManager()->getConnection()->isTransactionActive()) {
                $this->getEntityManager()->rollback();
            }
            throw $e;
        }
    }
} 