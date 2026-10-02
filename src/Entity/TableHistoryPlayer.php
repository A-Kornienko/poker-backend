<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTrait;
use App\ValueObject\Card;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'table_history_player')]
#[ORM\HasLifecycleCallbacks]
class TableHistoryPlayer
{
    use DateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TableHistory::class, inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'table_history_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private TableHistory $tableHistory;

    #[ORM\Column(name: 'place', type: Types::INTEGER, options: ['default' => 0])]
    private int $place = 0;

    #[ORM\Column(name: 'seat', type: Types::INTEGER, options: ['default' => 0])]
    private int $seat = 0;

    #[ORM\Column(name: 'login', type: Types::STRING, length: 70)]
    private string $login = '';

    #[ORM\Column(name: 'cards', type: Types::JSON, nullable: true)]
    private ?array $cards = [];

    #[ORM\Column(name: 'stack_before', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.0])]
    private string $stackBefore = '0.00';

    #[ORM\Column(name: 'stack_after', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.0])]
    private string $stackAfter = '0.00';

    #[ORM\Column(name: 'net_change', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.0])]
    private string $netChange = '0.00';

    #[ORM\Column(name: 'is_active', type: Types::BOOLEAN, options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'is_folded', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isFolded = false;

    #[ORM\Column(name: 'is_all_in', type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isAllIn = false;

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

    public function getPlace(): int
    {
        return $this->place;
    }

    public function setPlace(int $place): static
    {
        $this->place = $place;

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

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getCards(): array
    {
        return $this->cards ?? [];
    }

    public function setCards(array $cards): static
    {
        $this->cards = array_map(
            static fn(Card|array $card): array => $card instanceof Card ? $card->toArray() : $card,
            $cards
        );

        return $this;
    }

    public function getStackBefore(): float
    {
        return (float) $this->stackBefore;
    }

    public function setStackBefore(float $stackBefore): static
    {
        $this->stackBefore = (string) $stackBefore;

        return $this;
    }

    public function getStackAfter(): float
    {
        return (float) $this->stackAfter;
    }

    public function setStackAfter(float $stackAfter): static
    {
        $this->stackAfter = (string) $stackAfter;

        return $this;
    }

    public function getNetChange(): float
    {
        return (float) $this->netChange;
    }

    public function setNetChange(float $netChange): static
    {
        $this->netChange = (string) $netChange;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isFolded(): bool
    {
        return $this->isFolded;
    }

    public function setIsFolded(bool $isFolded): static
    {
        $this->isFolded = $isFolded;

        return $this;
    }

    public function isAllIn(): bool
    {
        return $this->isAllIn;
    }

    public function setIsAllIn(bool $isAllIn): static
    {
        $this->isAllIn = $isAllIn;

        return $this;
    }
}
