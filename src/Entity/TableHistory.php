<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\DateTrait;
use App\Enum\Round;
use App\Repository\TableHistoryRepository;
use App\ValueObject\TableHistory\BlindsTableHistory;
use App\ValueObject\TableHistory\PlayerTableHistory;
use App\ValueObject\TableHistory\PotTableHistory;
use App\ValueObject\TableHistory\RoundActionTableHistory;
use App\ValueObject\TableHistory\WinnerTableHistory;
use App\ValueObject\Card;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TableHistoryRepository::class)]
#[ORM\HasLifecycleCallbacks]
class TableHistory
{
    use DateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tableHistory')]
    #[ORM\JoinColumn(name: 'table_id', referencedColumnName: 'id')]
    private Table $table;

    #[ORM\Column(name: 'session', type: Types::STRING, nullable: false)]
    private ?string $session = null;

    #[ORM\Column(name: 'hand_number', type: Types::INTEGER, options: ['default' => 1])]
    private int $handNumber = 1;

    #[ORM\Column(name: 'game_type', type: Types::STRING, options: ['default' => 'cash'])]
    private string $gameType = 'cash';

    #[ORM\Column(name: 'status', type: Types::STRING, options: ['default' => 'started'])]
    private string $status = 'started';

    #[ORM\Column(name: 'dealer_seat', type: Types::INTEGER, options: ['default' => 1])]
    private int $dealerSeat = 1;

    #[ORM\Column(name: 'small_blind_place', type: Types::INTEGER, options: ['default' => 0])]
    private int $smallBlindPlace = 0;

    #[ORM\Column(name: 'big_blind_place', type: Types::INTEGER, options: ['default' => 0])]
    private int $bigBlindPlace = 0;

    #[ORM\Column(name: 'small_blind', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.1])]
    private string $smallBlind = '0.10';

    #[ORM\Column(name: 'big_blind', type: Types::DECIMAL, precision: 10, scale: 2, options: ['default' => 0.2])]
    private string $bigBlind = '0.20';

    #[ORM\Column(name: 'started_at', type: Types::INTEGER, options: ['default' => 0])]
    private int $startedAt = 0;

    #[ORM\Column(name: 'ended_at', type: Types::INTEGER, nullable: true)]
    private ?int $endedAt = null;

    #[ORM\Column(name: 'board_cards', type: Types::JSON, nullable: true)]
    private ?array $boardCards = [];

    /**
     * @var Collection<int, TableHistoryPlayer>
     */
    #[ORM\OneToMany(targetEntity: TableHistoryPlayer::class, mappedBy: 'tableHistory', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $players;

    /**
     * @var Collection<int, TableHistoryAction>
     */
    #[ORM\OneToMany(targetEntity: TableHistoryAction::class, mappedBy: 'tableHistory', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $actions;

    /**
     * @var Collection<int, TableHistoryPot>
     */
    #[ORM\OneToMany(targetEntity: TableHistoryPot::class, mappedBy: 'tableHistory', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $pots;

    /**
     * @var Collection<int, TableHistoryWinner>
     */
    #[ORM\OneToMany(targetEntity: TableHistoryWinner::class, mappedBy: 'tableHistory', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $winners;

    /**
     * @var Collection<int, TableHistoryResult>
     */
    #[ORM\OneToMany(targetEntity: TableHistoryResult::class, mappedBy: 'tableHistory', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $results;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->actions = new ArrayCollection();
        $this->pots    = new ArrayCollection();
        $this->winners = new ArrayCollection();
        $this->results = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function setTable(Table $table): static
    {
        $this->table = $table;

        return $this;
    }

    public function getSession(): ?string
    {
        return $this->session;
    }

    public function setSession(string $session): static
    {
        $this->session = $session;

        return $this;
    }

    public function getHandNumber(): int
    {
        return $this->handNumber;
    }

    public function setHandNumber(int $handNumber): static
    {
        $this->handNumber = $handNumber;

        return $this;
    }

    public function getGameType(): string
    {
        return $this->gameType;
    }

    public function setGameType(string $gameType): static
    {
        $this->gameType = $gameType;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getPlayers(): array
    {
        $players = [];
        foreach ($this->players as $player) {
            $players[$player->getSeat()] = (new PlayerTableHistory())
                ->setPlace($player->getPlace())
                ->setLogin($player->getLogin())
                ->setCards($player->getCards())
                ->setStack($player->getStackAfter())
                ->setIsMyPlayer(false);
        }

        ksort($players);

        return $players;
    }

    public function setPlayers(array $players): static
    {
        $this->players->clear();

        foreach ($players as $player) {
            $historyPlayer = (new TableHistoryPlayer())
                ->setTableHistory($this)
                ->setPlace((int) $player->getPlace())
                ->setSeat((int) $player->getPlace())
                ->setLogin($player->getLogin())
                ->setStackAfter((float) $player->getStack())
                ->setStackBefore((float) $player->getStack())
                ->setNetChange(0.0);

            $this->players->add($historyPlayer);
        }

        return $this;
    }

    public function addPlayer(TableHistoryPlayer $player): static
    {
        if ($this->players->contains($player)) {
            return $this;
        }

        if ($this->getPlayerRecord($player->getLogin(), $player->getSeat()) !== null) {
            return $this;
        }

        $player->setTableHistory($this);
        $this->players->add($player);

        return $this;
    }

    public function getPlayerRecord(string $login, int $seat): ?TableHistoryPlayer
    {
        foreach ($this->players as $player) {
            if ($player->getLogin() === $login && $player->getSeat() === $seat) {
                return $player;
            }
        }

        return null;
    }

    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(TableHistoryAction $action): static
    {
        if (!$this->actions->contains($action)) {
            $action->setTableHistory($this);
            $this->actions->add($action);
        }

        return $this;
    }

    public function getBlinds(): BlindsTableHistory
    {
        return (new BlindsTableHistory())->fromArray([
            'smallBlindPlace' => $this->smallBlindPlace,
            'bigBlindPlace' => $this->bigBlindPlace,
            'smallBlind' => (float) $this->smallBlind,
            'bigBlind' => (float) $this->bigBlind,
        ]);
    }

    public function setBlinds(BlindsTableHistory $blinds): static
    {
        $this->smallBlindPlace = (int) $blinds->getSmallBlindPlace();
        $this->bigBlindPlace = (int) $blinds->getBigBlindPlace();
        $this->smallBlind = (string) $blinds->getSmallBlind();
        $this->bigBlind = (string) $blinds->getBigBlind();

        return $this;
    }

    public function getDealer(): int
    {
        return $this->dealerSeat;
    }

    public function setDealer(int $dealer): static
    {
        $this->dealerSeat = $dealer;

        return $this;
    }

    public function getDealerSeat(): int
    {
        return $this->dealerSeat;
    }

    public function setDealerSeat(int $dealerSeat): static
    {
        $this->dealerSeat = $dealerSeat;

        return $this;
    }

    public function getStartedAt(): int
    {
        return $this->startedAt;
    }

    public function setStartedAt(int $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?int
    {
        return $this->endedAt;
    }

    public function setEndedAt(?int $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getCards(?bool $toArray = false): array
    {
        if ($toArray) {
            return $this->boardCards ?? [];
        }

        return array_map(
            static fn(array $card): Card => (new Card())->fromArray($card),
            $this->boardCards ?? []
        );
    }

    public function setCards(Card ...$cards): static
    {
        $this->boardCards = array_map(
            static fn(Card $card): array => $card->toArray(),
            $cards
        );

        return $this;
    }

    public function getPreflop(): array
    {
        return $this->filterActionsByRound(Round::PreFlop);
    }

    public function setPreflop(RoundActionTableHistory ...$preflop): static
    {
        $this->replaceActionsByRound(Round::PreFlop, $preflop);

        return $this;
    }

    public function addPreflop(RoundActionTableHistory $roundAction): static
    {
        $this->appendAction(Round::PreFlop, $roundAction);

        return $this;
    }

    public function getFlop(): array
    {
        return $this->filterActionsByRound(Round::Flop);
    }

    public function setFlop(RoundActionTableHistory ...$flop): static
    {
        $this->replaceActionsByRound(Round::Flop, $flop);

        return $this;
    }

    public function addFlop(RoundActionTableHistory $roundAction): static
    {
        $this->appendAction(Round::Flop, $roundAction);

        return $this;
    }

    public function getTurn(): array
    {
        return $this->filterActionsByRound(Round::Turn);
    }

    public function setTurn(RoundActionTableHistory ...$turn): static
    {
        $this->replaceActionsByRound(Round::Turn, $turn);

        return $this;
    }

    public function addTurn(RoundActionTableHistory $roundAction): static
    {
        $this->appendAction(Round::Turn, $roundAction);

        return $this;
    }

    public function getRiver(): array
    {
        return $this->filterActionsByRound(Round::River);
    }

    public function setRiver(RoundActionTableHistory ...$river): static
    {
        $this->replaceActionsByRound(Round::River, $river);

        return $this;
    }

    public function addRiver(RoundActionTableHistory $roundAction): static
    {
        $this->appendAction(Round::River, $roundAction);

        return $this;
    }

    public function getPot(): PotTableHistory
    {
        $pot = new PotTableHistory();
        $rounds = [
            Round::PreFlop->value => null,
            Round::Flop->value => null,
            Round::Turn->value => null,
            Round::River->value => null,
        ];

        foreach ($this->pots as $historyPot) {
            $rounds[$historyPot->getRound()->value] = $historyPot->getAmount();
        }

        $pot->fromArray($rounds);

        return $pot;
    }

    public function setPot(PotTableHistory $pot): static
    {
        $this->pots->clear();

        foreach ([
            Round::PreFlop,
            Round::Flop,
            Round::Turn,
            Round::River,
        ] as $round) {
            $amount = $pot->getPreFlop();
            if ($round === Round::PreFlop) {
                $amount = $pot->getPreFlop();
            } elseif ($round === Round::Flop) {
                $amount = $pot->getFlop();
            } elseif ($round === Round::Turn) {
                $amount = $pot->getTurn();
            } elseif ($round === Round::River) {
                $amount = $pot->getRiver();
            }

            if ($amount !== null) {
                $this->pots->add((new TableHistoryPot())
                    ->setTableHistory($this)
                    ->setRound($round)
                    ->setType('main')
                    ->setAmount((float) $amount));
            }
        }

        return $this;
    }

    public function getWinners(): array
    {
        $winners = [];
        foreach ($this->winners as $winner) {
            $winners[] = (new WinnerTableHistory())
                ->setLogin($winner->getPlayer())
                ->setCombination($winner->getCombination())
                ->setSeat($winner->getSeat())
                ->setHandRank($winner->getHandRank())
                ->setHandCards($winner->getHandCards())
                ->setSum($winner->getAmountWon());
        }

        return $winners;
    }

    public function setWinners(WinnerTableHistory ...$winners): static
    {
        $this->winners->clear();

        foreach ($winners as $winner) {
            $this->winners->add((new TableHistoryWinner())
                ->setTableHistory($this)
                ->setPlayer($winner->getLogin())
                ->setSeat($winner->getSeat())
                ->setAmountWon((float) $winner->getSum())
                ->setHandRank($winner->getHandRank())
                ->setCombination($winner->getCombination()?->toArray())
                ->setHandCards($winner->getHandCards()));
        }

        return $this;
    }

    public function addWinner(WinnerTableHistory $winner): static
    {
        $this->winners->add((new TableHistoryWinner())
            ->setTableHistory($this)
            ->setPlayer($winner->getLogin())
            ->setSeat($winner->getSeat())
            ->setAmountWon((float) $winner->getSum())
            ->setHandRank($winner->getHandRank())
            ->setCombination($winner->getCombination()?->toArray())
            ->setHandCards($winner->getHandCards()));

        return $this;
    }

    public function getResults(): Collection
    {
        return $this->results;
    }

    public function addResult(TableHistoryResult $result): static
    {
        if (!$this->results->contains($result)) {
            $result->setTableHistory($this);
            $this->results->add($result);
        }

        return $this;
    }

    private function filterActionsByRound(Round $round): array
    {
        $items = [];

        foreach ($this->actions as $action) {
            if ($action->getRound() === $round) {
                $items[] = (new RoundActionTableHistory())
                    ->setLogin($action->getPlayer())
                    ->setPlace($action->getSeat())
                    ->setType($action->getActionType())
                    ->setBetType($action->getBetType())
                    ->setAmount($action->getAmount());
            }
        }

        return $items;
    }

    private function replaceActionsByRound(Round $round, array $items): void
    {
        foreach ($this->actions as $existingAction) {
            if ($existingAction->getRound() === $round) {
                $this->actions->removeElement($existingAction);
            }
        }

        foreach ($items as $item) {
            $this->appendAction($round, $item);
        }
    }

    private function appendAction(Round $round, RoundActionTableHistory $roundAction): void
    {
        $action = (new TableHistoryAction())
            ->setTableHistory($this)
            ->setPlayer($roundAction->getLogin())
            ->setSeat((int) $roundAction->getPlace())
            ->setRound($round)
            ->setActionType($roundAction->getType())
            ->setBetType($roundAction->getBetType())
            ->setAmount($roundAction->getAmount())
            ->setSequenceNumber($this->actions->count() + 1);

        $this->actions->add($action);
    }
}
