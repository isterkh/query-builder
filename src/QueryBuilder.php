<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder;

use Closure;
use InvalidArgumentException;
use Isterkh\QueryBuilder\Clauses\WithClause;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Queries\SelectQuery;
use SebastianBergmann\CodeCoverage\Test\TestSize\Known;

class QueryBuilder
{


    public function __construct(
        protected ?ConnectionInterface $connection = null,
        protected WithClause           $cte = new WithClause()
    )
    {
    }


    protected function newInstance(): static
    {
        return new static($this->connection);
    }

    protected function newSelectQuery(): SelectQuery
    {
        return new SelectQuery($this->cte)
            ->setConnection($this->connection);
    }

    public function with(string $alias, Closure $callback): static
    {
        $this->cte->add($alias, $callback($this->newInstance()));
        return $this;
    }

    public function select(array|string ...$columns): SelectQuery
    {
        return $this->newSelectQuery()->select(...$columns);
    }

    public function selectRaw(string $sql, array $bindings = []): SelectQuery
    {
        return $this->newSelectQuery()->selectRaw($sql, $bindings);
    }


}