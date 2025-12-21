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
        bool $isOr = false
    )
    {
        $this->rootConditionGroup = new ConditionGroup($isOr);
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
}