<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Traits;

use Isterkh\QueryBuilder\Clauses\WhereClause;
use Isterkh\QueryBuilder\Condition\ConditionGroup;

trait HasWhereTrait
{
    protected ?WhereClause $where = null;

    public function where(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
    ): static {
        $this->getOrCreateWhere()->where($column, $operatorOrValue, $value);

        return $this;
    }

    public function orWhere(
        \Closure|string $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
    ): static {
        $this->getOrCreateWhere()->orWhere($column, $operatorOrValue, $value);

        return $this;
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function whereRaw(string $sql, array $bindings = []): static
    {
        $this->getOrCreateWhere()->whereRaw($sql, $bindings);

        return $this;
    }

    public function getWhere(): ?WhereClause
    {
        return $this->where;
    }

    protected function getOrCreateWhere(): WhereClause
    {
        return $this->where ??= new WhereClause(
            new ConditionGroup()
        );
    }
}
