<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder;

use Closure;
use InvalidArgumentException;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Queries\SelectQuery;
use SebastianBergmann\CodeCoverage\Test\TestSize\Known;

class QueryBuilder
{

    /**
     * @param array<string, QueryInterface> $cte
     */
    protected array $cte = [];
    public function __construct(
        protected CompilerInterface $compiler,
    )
    {
    }

    protected function newSelectQuery(): SelectQuery
    {
        return new SelectQuery($this->compiler, $this->cte);
    }

    public function with(string $alias, Closure $callback): static
    {
        $this->cte[$alias] = $callback(new static($this->compiler));
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