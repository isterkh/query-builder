<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Clauses;

use Isterkh\QueryBuilder\Contracts\QueryInterface;

class UnionClause
{
    public function __construct(
        protected QueryInterface $query,
        protected bool $isAll = false
    )
    {
    }
    public function getQuery(): QueryInterface
    {
        return $this->query;
    }
    public function isAll(): bool
    {
        return $this->isAll;
    }
}