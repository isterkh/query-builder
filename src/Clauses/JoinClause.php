<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Clauses;

use Closure;
use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\HasConditionInterface;
use Isterkh\QueryBuilder\Enum\JoinTypeEnum;
use Isterkh\QueryBuilder\Traits\HasConditionTrait;
use Isterkh\QueryBuilder\Traits\WhereAliasTrait;

class JoinClause implements HasConditionInterface
{
    use HasConditionTrait;
    use WhereAliasTrait;
    public function __construct(
        protected FromClause $from,
        protected JoinTypeEnum $type,
        protected ConditionGroup $conditions,
    )
    {
    }

    public function getConditions(): ConditionGroup
    {
        return $this->conditions;
    }

    public function on(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static
    {
        return $this->add($column, $operatorOrValue, $value, false, true);
    }
    public function where(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static
    {
        return $this->add($column, $operatorOrValue, $value, false);
    }
    public function orOn(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static
    {
        return $this->add($column, $operatorOrValue, $value, true, true);
    }
    public function orWhere(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static
    {
        return $this->add($column, $operatorOrValue, $value, true);
    }

    public function getType(): JoinTypeEnum
    {
        return $this->type;
    }
    public function getFrom(): FromClause
    {
        return $this->from;
    }
}