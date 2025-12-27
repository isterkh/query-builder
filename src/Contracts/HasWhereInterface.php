<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Contracts;

use Isterkh\QueryBuilder\Components\WhereClause;

interface HasWhereInterface
{
    public function where(\Closure|string $column, mixed $operatorOrValue = null, mixed $value = null): static;

    public function orWhere(\Closure|string $column, mixed $operatorOrValue = null, mixed $value = null): static;

    /**
     * @param array<int, mixed> $bindings
     */
    public function whereRaw(string $sql, array $bindings = []): static;

    public function getWhere(): ?WhereClause;
}
