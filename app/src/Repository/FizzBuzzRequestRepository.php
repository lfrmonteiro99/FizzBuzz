<?php

namespace App\Repository;

use App\Dto\FizzBuzzRequestDto;
use App\Entity\FizzBuzzRequest;
use App\Interface\FizzBuzzRequestRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
        return $this->createQueryBuilder('r')
            ->orderBy('r.hits', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find an existing request with the given parameters or create a new one
     *
     * @param FizzBuzzRequestDto $dto The DTO containing the request parameters
     * @return FizzBuzzRequest The request entity
     * @throws \Doctrine\ORM\ORMException If there's an error during database operations
     */
    public function findOrCreateRequest(FizzBuzzRequestDto $dto): FizzBuzzRequest
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.start = :start')
            ->andWhere('r.limit = :limit')
            ->andWhere('r.divisor1 = :divisor1')
            ->andWhere('r.divisor2 = :divisor2')
            ->andWhere('r.str1 = :str1')
            ->andWhere('r.str2 = :str2')
            ->setParameter('start', $dto->getStart())
            ->setParameter('limit', $dto->getLimit())
            ->setParameter('divisor1', $dto->getDivisor1())
            ->setParameter('divisor2', $dto->getDivisor2())
            ->setParameter('str1', $dto->getStr1())
            ->setParameter('str2', $dto->getStr2())
            ->setMaxResults(1);

        $request = $qb->getQuery()->getOneOrNullResult();

        if (!$request) {
            $request = new FizzBuzzRequest(
                $dto->getLimit(),
                $dto->getDivisor1(),
                $dto->getDivisor2(),
                $dto->getStr1(),
                $dto->getStr2(),
                $dto->getStart()
            );
            $this->em->persist($request);
        }

        return $request;
    }

    public function incrementHits(FizzBuzzRequest $request): void
    {
        $request->incrementHits();
        $this->em->flush();
    }

    public function markAsProcessed(FizzBuzzRequest $request): void
    {
        $request->markAsProcessed();
        $this->em->flush();
    }

    public function markAsFailed(FizzBuzzRequest $request): void
    {
        $request->markAsFailed();
        $this->em->flush();
    }

    public function getMostFrequentRequest(): ?FizzBuzzRequest
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.hits', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function incrementHitsWithLock(FizzBuzzRequest $request): void
    {
        try {
            // Refresh the entity to get the latest version
            $this->em->refresh($request);
            
            // Increment hits
            $request->incrementHits();
            
            // Flush changes with optimistic locking
            $this->em->flush();
        } catch (OptimisticLockException $e) {
            // If optimistic locking fails, retry once
            $this->em->refresh($request);
            $request->incrementHits();
            $this->em->flush();
        }
    }

    public function findPendingRequests(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.trackingState = :state')
            ->andWhere('r.createdAt < :threshold')
            ->setParameter('state', 'pending')
            ->setParameter('threshold', new \DateTimeImmutable('-5 minutes'))
            ->getQuery()
            ->getResult();
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