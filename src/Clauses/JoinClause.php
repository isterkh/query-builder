<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Clauses;

use Isterkh\QueryBuilder\Condition\ConditionGroup;
use Isterkh\QueryBuilder\Contracts\HasConditionInterface;
use Isterkh\QueryBuilder\Enum\JoinTypeEnum;
use Isterkh\QueryBuilder\Traits\HasConditionTrait;
use Isterkh\QueryBuilder\Traits\WhereAliasTrait;
use Isterkh\QueryBuilder\ValueObjects\TableReference;

class JoinClause implements HasConditionInterface
{
    use HasConditionTrait;
    use WhereAliasTrait;

    public function __construct(
        protected TableReference $from,
        protected JoinTypeEnum $type,
        protected ConditionGroup $conditions,
    ) {}

    public function getConditions(): ConditionGroup
    {
        return $this->conditions;
    }

    public function on(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static {
        return $this->add($column, $operatorOrValue, $value, false, true);
    }

    public function where(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static {
        return $this->add($column, $operatorOrValue, $value, false);
    }

    public function orOn(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static {
        return $this->add($column, $operatorOrValue, $value, true, true);
    }

    public function orWhere(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null
    ): static {
        return $this->add($column, $operatorOrValue, $value, true);
    }

    public function getType(): JoinTypeEnum
    {
        return $this->type;
    }

    public function getFrom(): TableReference
    {
        return $this->from;
    }

    protected function newInstance(): self
    {
        return new self($this->from, $this->type, new ConditionGroup());
    }
}
