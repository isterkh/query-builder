<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Components;

class TableReference
{
    public function __construct(
        protected string $table,
        protected ?string $alias = null
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }
}
