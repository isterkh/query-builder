<?php
declare(strict_types=1);

namespace Isterkh\QueryBuilder\Queries;
use Closure;
use Isterkh\QueryBuilder\Clauses\FromClause;
use Isterkh\QueryBuilder\Clauses\WhereClause;
use Isterkh\QueryBuilder\Condition\Condition;
use Isterkh\QueryBuilder\Condition\ConditionGroup;

class SelectQuery
{
    protected FromClause $from;
    protected ?WhereClause $where = null;
    public function __construct(
        protected array $columns = ['*']
    )
    {
    }

    public function from(string $table): static
    {
        $this->from = new FromClause($table);
        return $this;
    }

    public function where(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
        bool $isOr = false
    ): static
    {
        if ($this->where === null) {
            $this->where = new WhereClause($isOr);
        }
        $this->where->where($column, $operatorOrValue, $value, $isOr);
        return $this;
    }

    public function orWhere(
        string|Closure $column,
        mixed $operatorOrValue = null,
        mixed $value = null,
    ): static
    {
        return $this->where($column, $operatorOrValue, $value, true);
    }

    public function getColumns(): array
    {
        return $this->columns;
    }
    public function getFrom(): FromClause
    {
        return $this->from;
    }
    public function getWhere(): ?WhereClause
    {
        return $this->where;
    }
}