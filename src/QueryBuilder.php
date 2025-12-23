<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder;

use Closure;
use InvalidArgumentException;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Queries\SelectQuery;

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

    public function with(string $alias, Closure $callback): static
    {
        $this->cte[$alias] = $callback(new static($this->compiler));
        return $this;
    }

    public function select(array|string ...$columns): SelectQuery
    {
        return new SelectQuery($this->compiler, $this->cte);
    }



}