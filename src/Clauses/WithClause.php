<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Clauses;

use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\QueryBuilder;

class WithClause
{
    /**
     * @var array<string, QueryInterface>
     */
    protected array $queries = [];

    public function __construct(
        protected bool $isRecursive = false
    ) {}

    public function setRecursive(bool $isRecursive): static
    {
        $this->isRecursive = $isRecursive;

        return $this;
    }

    public function add(string $alias, QueryBuilder $query): static
    {
        $this->queries[$alias] = $query;

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->queries);
    }

    public function isRecursive(): bool
    {
        return $this->isRecursive;
    }

    /**
     * @return QueryInterface[]
     */
    public function getQueries(): array
    {
        return $this->queries;
    }
}
