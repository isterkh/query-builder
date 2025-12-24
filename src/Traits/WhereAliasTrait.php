<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Traits;

trait WhereAliasTrait
{
    public function whereBetween(\Closure|string $column, int|string $firstValue, int|string $secondValue): static
    {
        return $this->where($column, 'between', [$firstValue, $secondValue]);
    }

    public function whereNotBetween(\Closure|string $column, int|string $firstValue, int|string $secondValue): static
    {
        return $this->where($column, 'not between', [$firstValue, $secondValue]);
    }

    public function orWhereBetween(\Closure|string $column, int|string $firstValue, int|string $secondValue): static
    {
        return $this->orWhere($column, 'between', [$firstValue, $secondValue]);
    }

    public function orWhereNotBetween(\Closure|string $column, int|string $firstValue, int|string $secondValue): static
    {
        return $this->orWhere($column, 'not between', [$firstValue, $secondValue]);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereIn(\Closure|string $column, array $values): static
    {
        return $this->where($column, 'in', $values);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereNotIn(\Closure|string $column, array $values): static
    {
        return $this->where($column, 'not in', $values);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function orWhereIn(\Closure|string $column, array $values): static
    {
        return $this->orWhere($column, 'in', $values);
    }

    /**
     * @param array<int, mixed> $values
     */
    public function orWhereNotIn(\Closure|string $column, array $values): static
    {
        return $this->orWhere($column, 'not in', $values);
    }
}
