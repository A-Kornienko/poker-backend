<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTrait;
use App\Enum\Round;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'table_history_pot')]
#[ORM\HasLifecycleCallbacks]
class TableHistoryPot
{
    use DateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TableHistory::class, inversedBy: 'pots')]
    #[ORM\JoinColumn(name: 'table_history_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private TableHistory $tableHistory;

    #[ORM\Column(name: 'round', type: Types::STRING, enumType: Round::class)]
    private Round $round;

    #[ORM\Column(name: 'type', type: Types::STRING, options: ['default' => 'main'])]
    private string $type = 'main';

    #[ORM\Column(name: 'amount', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.0])]
    private string $amount = '0.00';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableHistory(): TableHistory
    {
        return $this->tableHistory;
    }

    public function setTableHistory(TableHistory $tableHistory): static
    {
        $this->tableHistory = $tableHistory;

        return $this;
    }

    public function getRound(): Round
    {
        return $this->round;
    }

    public function setRound(Round $round): static
    {
        $this->round = $round;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getAmount(): float
    {
        return (float) $this->amount;
    }

    public function setAmount(float $amount): static
    {
        $this->amount = (string) $amount;

        return $this;
    }
}
