<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Clauses;

use Isterkh\QueryBuilder\Queries\SelectQuery;

class UnionClause
{
    public function __construct(
        protected SelectQuery $query,
        protected bool $isAll = false
    ) {}

    public function getQuery(): SelectQuery
    {
        return $this->query;
    }

    public function isAll(): bool
    {
        return $this->isAll;
    }
}
