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
        return new SelectQuery($this->compiler, $this->normalizeColumns($columns), $this->cte);
    }


    protected function normalizeColumns(array $columns): array
    {
        if (empty($columns)) {
            return ['*'];
        }
        if (count($columns) === 1 && is_array($columns[0])) {
            return $columns[0];
        }
        foreach ($columns as $key => $column) {
            if (!is_string($column) || !(is_int($key) || is_string($key))) {
                throw new InvalidArgumentException('Wrong argument');
            }
        }
        return $columns;
    }
}