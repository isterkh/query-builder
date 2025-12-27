<?php

declare(strict_types=1);

namespace Tests;

use Isterkh\QueryBuilder\Compilers\Compiler;
use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\QueryBuilder;

class FakeConnection implements ConnectionInterface
{
    public function __construct(
        protected Compiler $compiler,
    ) {}

    public function query(QueryBuilder $query): iterable
    {
        return [];
    }

    public function execute(QueryBuilder $query): int
    {
        return 0;
    }

    public function getCompiled(QueryBuilder $query): Expression
    {
        return $this->compiler->compile($query);
    }

    public function beginTransaction(): bool
    {
        return false;
    }

    public function commit(): bool
    {
        return false;
    }

    public function rollback(): bool
    {
        return false;
    }
}
