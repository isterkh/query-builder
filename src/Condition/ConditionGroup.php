<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Condition;

class ConditionGroup
{

    protected array $conditions = [];
    public function __construct(
        protected bool $isOr = false
    )
    {
    }

    public function add(Condition|ConditionGroup $condition): static
    {
        $this->conditions[] = $condition;
        return $this;
    }

    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function isOr(): bool
    {
        return $this->isOr;
    }

    public function getLast(): null|Condition|ConditionGroup
    {
        return $this->conditions[array_key_last($this->conditions)] ?? null;
    }

    public function pop(): static
    {
        array_pop($this->conditions);
        return $this;
    }

}