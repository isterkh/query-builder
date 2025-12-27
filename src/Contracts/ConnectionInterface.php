<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Contracts;

use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\QueryBuilder;

interface ConnectionInterface
{
    /**
     * @return iterable<mixed>
     */
    public function query(QueryBuilder $query): iterable;

    public function execute(QueryBuilder $query): int;

    public function getCompiled(QueryBuilder $query): Expression;

    public function beginTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;
}
