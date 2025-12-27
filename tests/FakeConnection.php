<?php

declare(strict_types=1);

namespace Tests;

use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;

class FakeConnection implements ConnectionInterface
{
    public function __construct(
        protected CompilerInterface $compiler,
    ) {}

    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    public function query(QueryInterface $query): iterable
    {
        return [];
    }

    public function execute(QueryInterface $query): int
    {
        return 0;
    }

    public function getCompiled(QueryInterface $query): Expression
    {
        return $this->getCompiler()->compile($query);
    }
}
