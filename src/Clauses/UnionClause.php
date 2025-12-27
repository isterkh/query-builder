<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Clauses;

use Isterkh\QueryBuilder\QueryBuilder;
use Isterkh\QueryBuilder\Queries\SelectQuery;

class UnionClause
{
    public function __construct(
        protected QueryBuilder $query,
        protected bool         $isAll = false
    ) {}

    public function getQuery(): QueryBuilder
    {
        return $this->query;
    }

    public function isAll(): bool
    {
        return $this->isAll;
    }
}
