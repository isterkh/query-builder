<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Condition;

use Isterkh\QueryBuilder\Expressions\Expression;

class ConditionGroup
{
    /**
     * @var Condition[]|ConditionGroup[]|Expression[]
     */
    protected array $conditions = [];

    public function __construct(
        protected bool $isOr = false
    ) {}

    public function add(Condition|ConditionGroup|Expression $condition): static
    {
        $this->conditions[] = $condition;

        return $this;
    }

    /**
     * @return Condition[]|ConditionGroup[]|Expression[]
     */
    public function getConditions(): array
    {
        return $this->conditions;
    }

    public function isOr(): bool
    {
        return $this->isOr;
    }

    public function getLast(): Condition|ConditionGroup|Expression|null
    {
        return $this->conditions[array_key_last($this->conditions)] ?? null;
    }

    public function pop(): static
    {
        array_pop($this->conditions);

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->conditions);
    }
}
