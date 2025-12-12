<?php

namespace App\Repository;

use App\Entity\Item;
use App\Entity\Loan;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Loan>
 */
class LoanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Loan::class);
    }

public function isItemAlreadyLoaned(Item $item, \DateTime $start, \DateTime $endLoan): bool
{
    return (bool) $this->createQueryBuilder('l')
        ->select('COUNT(l.id)')
        ->where('l.item = :item')
        ->andWhere('l.start <= :endLoan')
        ->andWhere('l.endLoan >= :start')
        ->setParameter('item', $item)
        ->setParameter('start', $start)
        ->setParameter('endLoan', $endLoan)
        ->getQuery()
        ->getSingleScalarResult();
        }

        public function findByItemOwner(User $owner)
        {
            return $this->createQueryBuilder('l')
                ->join('l.item', 'i')
                ->andWhere('i.owner = :owner')
                ->setParameter('owner', $owner)
                ->getQuery()
                ->getResult()
            ;
        }

    //    public function findOneBySomeField($value): ?Loan
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
