<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'table_history_result')]
#[ORM\HasLifecycleCallbacks]
class TableHistoryResult
{
    use DateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: TableHistory::class, inversedBy: 'results')]
    #[ORM\JoinColumn(name: 'table_history_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private TableHistory $tableHistory;

    #[ORM\Column(name: 'player', type: Types::STRING, length: 70)]
    private string $player = '';

    #[ORM\Column(name: 'seat', type: Types::INTEGER, options: ['default' => 0])]
    private int $seat = 0;

    #[ORM\Column(name: 'delta_chips', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.0])]
    private string $deltaChips = '0.00';

    #[ORM\Column(name: 'final_stack', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.0])]
    private string $finalStack = '0.00';

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

    public function getDeltaChips(): float
    {
        return (float) $this->deltaChips;
    }

    public function setDeltaChips(float $deltaChips): static
    {
        $this->deltaChips = (string) $deltaChips;

        return $this;
    }

    public function getFinalStack(): float
    {
        return (float) $this->finalStack;
    }

    public function setFinalStack(float $finalStack): static
    {
        $this->finalStack = (string) $finalStack;

        return $this;
    }
}
