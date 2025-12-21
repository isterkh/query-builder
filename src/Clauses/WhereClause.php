<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Clauses;

use Closure;
use Isterkh\QueryBuilder\Condition\Condition;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\HasConditionInterface;
use Isterkh\QueryBuilder\Traits\HasConditionTrait;

class WhereClause implements HasConditionInterface
{
    use HasConditionTrait;

    public function __construct(
        bool $isOr = false
    )
    {
        $this->rootConditionGroup = new ConditionGroup($isOr);
    }

    public function where(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
        bool $isOr = false
    ): static
    {
        if ($column instanceof Closure) {
            $subWhere = new static();
            $column($subWhere);
            $this->add($subWhere->getConditions(), $isOr);
            return $this;
        }

        [$operator, $value] = $this->parseOperatorValue($operatorOrValue, $value);
        $condition = new Condition($column, $operator, $value);
        $this->add($condition, $isOr);

        return $this;
    }

    public function orWhere(string|Closure $column, mixed $operatorOrValue = null, mixed $value = null): static
    {
        return $this->where($column, $operatorOrValue, $value, true);
    }

}