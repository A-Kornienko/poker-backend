<?php

declare(strict_types=1);

namespace App\Handler\TableHistory;

use App\Entity\Table;
use App\Entity\TableHistory;
use App\Handler\AbstractHandler;
use App\Repository\TableHistoryRepository;
use App\Response\TableHistoryResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class GetTableHistoryListHandler extends AbstractHandler
{
    public function __construct(
        protected Security $security,
        protected TranslatorInterface $translator,
        protected TableHistoryRepository $tableHistoryRepository
    ) {
        parent::__construct($security, $translator);
    }

    public function __invoke(Table $table, Request $request): array
    {
        $page  = $request->query->getInt(static::REQUEST_PAGE, 1);
        $limit = $request->query->getInt(static::REQUEST_LIMIT, 20);
        $dateFrom = $this->parseDate($request->query->get('dateFrom'));
        $dateTo   = $this->parseDate($request->query->get('dateTo'));

        $tableHistoryCollection = $this->tableHistoryRepository->getCollection(
            $table,
            $page,
            $limit,
            $dateFrom,
            $dateTo,
        );
        $currentLogin = $this->security->getUser()?->getLogin();
        $items = array_map(
            static fn(TableHistory $tableHistory): array => TableHistoryResponse::list($tableHistory, $currentLogin),
            $tableHistoryCollection['items']
        );

        return [
            'items'      => $items,
            'pagination' => [
                'total' => $tableHistoryCollection['total'],
                'page'  => $page,
                'limit' => $limit,
                'pages' => $tableHistoryCollection['total'] ? ceil($tableHistoryCollection['total'] / $limit) : 0,
            ],
        ];
    }

    private function parseDate(?string $date): ?int
    {
        if ($date === null || $date === '') {
            return null;
        }

        try {
            $parsedDate = new \DateTimeImmutable($date);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException(sprintf('Invalid date format: %s', $date), 400);
        }

        return $parsedDate->getTimestamp();
    }

}
