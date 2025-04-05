<?php

namespace App\Repository;

use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FizzBuzzRequest>
 */
class FizzBuzzRequestRepository extends ServiceEntityRepository implements FizzBuzzRequestRepositoryInterface
{
    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FizzBuzzRequest::class);
        $this->em = $this->getEntityManager();
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
            $request = $this->findOneBy([
                'limit' => $limit,
                'int1' => $int1,
                'int2' => $int2,
                'str1' => $str1,
                'str2' => $str2,
            ]);

            if (!$request) {
                $request = new FizzBuzzRequest($limit, $int1, $int2, $str1, $str2);
                $this->em->persist($request);
                $this->em->flush();
            }

            return $request;
        } catch (\Exception $e) {
            throw new \Doctrine\ORM\ORMException('Error finding or creating FizzBuzzRequest: ' . $e->getMessage());
        }
    }

    /**
     * Save a FizzBuzzRequest entity
     *
     * @param FizzBuzzRequest $request The request to save
     * @param bool $flush Whether to flush the entity manager
     * @return void
     */
    public function save(FizzBuzzRequest $request, bool $flush = false): void
    {
        $this->em->persist($request);
        if ($flush) {
            $this->em->flush();
        }
    }
} 