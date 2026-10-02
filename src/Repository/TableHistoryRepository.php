<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\{Table, TableHistory};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TableHistory>
 */
class TableHistoryRepository extends ServiceEntityRepository
{
    public const PAGE_LIMIT = 20;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TableHistory::class);
    }

    public function getNextHandNumber(Table $table): int
    {
        $lastHandNumber = $this->createQueryBuilder('tableHistory')
            ->select('MAX(tableHistory.handNumber)')
            ->andWhere('tableHistory.table = :table')
            ->setParameter('table', $table)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $lastHandNumber + 1;
    }

    public function getCollection(
        Table $table,
        int $page = 1,
        ?int $limit = null,
        ?int $dateFrom = null,
        ?int $dateTo = null,
    ): array
    {
        $limit ??= static::PAGE_LIMIT;
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->createQueryBuilder('tableHistory')
            ->andWhere('tableHistory.table = :table')
            ->setParameter('table', $table)
            ->orderBy('tableHistory.id', 'DESC');

        $this->addDateFilters($queryBuilder, $dateFrom, $dateTo);
        $this->excludeUnfinishedCurrentSession($queryBuilder, $table);
        $queryBuilder
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $items = $queryBuilder->getQuery()->getResult();

        $countQueryBuilder = $this->createQueryBuilder('tableHistory')
            ->select('COUNT(tableHistory.id)')
            ->andWhere('tableHistory.table = :table')
            ->setParameter('table', $table);

        $this->addDateFilters($countQueryBuilder, $dateFrom, $dateTo);
        $this->excludeUnfinishedCurrentSession($countQueryBuilder, $table);

        return [
            'items' => array_values($items),
            'total' => (int) $countQueryBuilder->getQuery()->getSingleScalarResult(),
        ];
    }

    private function excludeUnfinishedCurrentSession($queryBuilder, Table $table): void
    {
        $currentSession = $table->getSession();
        if ($currentSession === null) {
            return;
        }

        $queryBuilder
            ->andWhere('(tableHistory.session != :currentSession OR tableHistory.status = :finishedStatus)')
            ->setParameter('currentSession', $currentSession)
            ->setParameter('finishedStatus', 'finished');
    }

    private function addDateFilters($queryBuilder, ?int $dateFrom, ?int $dateTo): void
    {
        if ($dateFrom !== null && $dateTo !== null) {
            $queryBuilder
                ->andWhere('tableHistory.createdAt BETWEEN :dateFrom AND :dateTo')
                ->setParameter('dateFrom', $dateFrom)
                ->setParameter('dateTo', $dateTo);

            return;
        }

        if ($dateFrom !== null) {
            $queryBuilder
                ->andWhere('tableHistory.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        }

        if ($dateTo !== null) {
            $queryBuilder
                ->andWhere('tableHistory.createdAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        }
    }
}
