<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Clauses;

use Closure;
use Isterkh\QueryBuilder\Condition\Condition;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\HasConditionInterface;
use Isterkh\QueryBuilder\Traits\HasConditionTrait;

class HavingClause implements HasConditionInterface
{
    use HasConditionTrait;
    public function __construct(
        protected ConditionGroup $conditions
    ) {

    }

    public function having(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static
    {
        return $this->add($column, $operatorOrValue, $value);
    }

    public function orHaving(string|Closure $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        return $this->add($column, $operatorOrValue, $value, true);
    }

    /**
     * @param string $sql
     * @param array<int, mixed> $bindings
     * @return static
     */
    public function havingRaw(string $sql, array $bindings = []): static
    {
        return $this->add($this->rawExpression($sql, $bindings));
    }

    public function getConditions(): ConditionGroup
    {
        return $this->conditions;
    }
}