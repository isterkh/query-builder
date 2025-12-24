<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Traits;

use Closure;

trait WhereAliasTrait
{
    public function whereBetween(string|Closure $column, int|string $firstValue, int|string $secondValue): static {
        return $this->where($column, 'between', [$firstValue, $secondValue]);
    }
    public function whereNotBetween(string|Closure $column, int|string $firstValue, int|string $secondValue): static {
        return $this->where($column, 'not between', [$firstValue, $secondValue]);
    }
    public function orWhereBetween(string|Closure $column, int|string $firstValue, int|string $secondValue): static {
        return $this->orWhere($column, 'between', [$firstValue, $secondValue]);
    }
    public function orWhereNotBetween(string|Closure $column, int|string $firstValue, int|string $secondValue): static {
        return $this->orWhere($column, 'not between', [$firstValue, $secondValue]);
    }

    /**
     * @param string|Closure $column
     * @param array<int, mixed> $values
     * @return static
     */
    public function whereIn(string|Closure $column, array $values): static {
        return $this->where($column, 'in', $values);
    }

    /**
     * @param string|Closure $column
     * @param array<int, mixed> $values
     * @return static
     */
    public function whereNotIn(string|Closure $column, array $values): static {
        return $this->where($column, 'not in', $values);
    }

    /**
     * @param string|Closure $column
     * @param array<int, mixed> $values
     * @return static
     */
    public function orWhereIn(string|Closure $column, array $values): static {
        return $this->orWhere($column, 'in', $values);
    }

    /**
     * @param string|Closure $column
     * @param array<int, mixed> $values
     * @return static
     */
    public function orWhereNotIn(string|Closure $column, array $values): static {
        return $this->orWhere($column, 'not in', $values);
    }


}