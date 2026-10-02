<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTrait;
use App\Enum\BetType;
use App\Enum\Round;
use App\Enum\RoundActionType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'table_history_action')]
#[ORM\HasLifecycleCallbacks]
class TableHistoryAction
{
    use DateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TableHistory::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(name: 'table_history_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private TableHistory $tableHistory;

    #[ORM\Column(name: 'player', type: Types::STRING, length: 70)]
    private string $player = '';

    #[ORM\Column(name: 'seat', type: Types::INTEGER, options: ['default' => 0])]
    private int $seat = 0;

    #[ORM\Column(name: 'round', type: Types::STRING, enumType: Round::class)]
    private Round $round;

    #[ORM\Column(name: 'action_type', type: Types::STRING, enumType: RoundActionType::class)]
    private RoundActionType $actionType;

    #[ORM\Column(name: 'bet_type', type: Types::STRING, enumType: BetType::class, nullable: true)]
    private ?BetType $betType = null;

    #[ORM\Column(name: 'amount', type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(name: 'sequence_number', type: Types::INTEGER, options: ['default' => 0])]
    private int $sequenceNumber = 0;

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

    public function getPlayer(): string
    {
        return $this->player;
    }

    public function setPlayer(string $player): static
    {
        $this->player = $player;

        return $this;
    }

    public function getSeat(): int
    {
        return $this->seat;
    }

    public function setSeat(int $seat): static
    {
        $this->seat = $seat;

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

    public function getActionType(): RoundActionType
    {
        return $this->actionType;
    }

    public function setActionType(RoundActionType $actionType): static
    {
        $this->actionType = $actionType;

        return $this;
    }

    public function getBetType(): ?BetType
    {
        return $this->betType;
    }

    public function setBetType(?BetType $betType): static
    {
        $this->betType = $betType;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount !== null ? (float) $this->amount : null;
    }

    public function setAmount(?float $amount): static
    {
        $this->amount = $amount !== null ? (string) $amount : null;

        return $this;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function setSequenceNumber(int $sequenceNumber): static
    {
        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }
}
