<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Clauses;

use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\HasConditionInterface;
use Isterkh\QueryBuilder\Traits\HasConditionTrait;

class WhereClause implements HasConditionInterface
{
    use HasConditionTrait;

    public function __construct(
        protected ConditionGroup $conditions
    ) {}

    public function where(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static {
        return $this->add($column, $operatorOrValue, $value);
    }

    public function orWhere(\Closure|string $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        return $this->add($column, $operatorOrValue, $value, true);
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function whereRaw(string $sql, array $bindings = []): static
    {
        return $this->add($this->rawExpression($sql, $bindings));
    }

    public function getConditions(): ConditionGroup
    {
        return $this->conditions;
    }
}
