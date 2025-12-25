<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder;

use Isterkh\QueryBuilder\Clauses\WithClause;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\Queries\SelectQuery;
use Isterkh\QueryBuilder\Queries\UpdateQuery;
use Isterkh\QueryBuilder\ValueObjects\TableReference;

class QueryBuilder
{
    public function __construct(
        protected ?ConnectionInterface $connection = null,
        protected WithClause $cte = new WithClause()
    ) {}

    public function with(string $alias, \Closure $callback): static
    {
        $this->cte->add($alias, $callback($this->newInstance()));

        return $this;
    }

    /**
     * @param array<mixed>|string ...$columns
     */
    public function select(array|string ...$columns): SelectQuery
    {
        return $this->newSelectQuery()->select(...$columns);
    }

    /**
     * @param array<int, mixed> $bindings
     */
    public function selectRaw(string $sql, array $bindings = []): SelectQuery
    {
        return $this->newSelectQuery()->selectRaw($sql, $bindings);
    }

    public function update(string $table, ?string $alias = null): UpdateQuery
    {
        return new UpdateQuery(
            new TableReference($table, $alias),
        )->setConnection($this->connection);
    }

    protected function newInstance(): self
    {
        return new self($this->connection);
    }

    protected function newSelectQuery(): SelectQuery
    {
        return new SelectQuery($this->cte)
            ->setConnection($this->connection)
        ;
    }
}
