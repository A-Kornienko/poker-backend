<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTrait;
use App\ValueObject\Card;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'table_history_winner')]
#[ORM\HasLifecycleCallbacks]
class TableHistoryWinner
{
    use DateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TableHistory::class, inversedBy: 'winners')]
    #[ORM\JoinColumn(name: 'table_history_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private TableHistory $tableHistory;

    #[ORM\Column(name: 'player', type: Types::STRING, length: 70)]
    private string $player = '';

    #[ORM\Column(name: 'seat', type: Types::INTEGER, options: ['default' => 0])]
    private int $seat = 0;

    #[ORM\Column(name: 'amount_won', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.0])]
    private string $amountWon = '0.00';

    #[ORM\Column(name: 'hand_rank', type: Types::STRING, length: 128, nullable: true)]
    private ?string $handRank = null;

    #[ORM\Column(name: 'combination', type: Types::JSON, nullable: true)]
    private ?array $combination = null;

    #[ORM\Column(name: 'hand_cards', type: Types::JSON, nullable: true)]
    private ?array $handCards = [];

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

    public function getAmountWon(): float
    {
        return (float) $this->amountWon;
    }

    public function setAmountWon(float $amountWon): static
    {
        $this->amountWon = (string) $amountWon;

        return $this;
    }

    public function getHandRank(): ?string
    {
        return $this->handRank;
    }

    public function setHandRank(?string $handRank): static
    {
        $this->handRank = $handRank;

        return $this;
    }

    public function getCombination(): ?array
    {
        return $this->combination;
    }

    public function setCombination(?array $combination): static
    {
        $this->combination = $combination;

        return $this;
    }

    public function getHandCards(): array
    {
        return $this->handCards ?? [];
    }

    public function setHandCards(?array $handCards): static
    {
        $this->handCards = array_map(
            static fn(Card|array $card): array => $card instanceof Card ? $card->toArray() : $card,
            $handCards ?? []
        );

        return $this;
    }
}
