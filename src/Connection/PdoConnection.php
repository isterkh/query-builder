<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Connection;

use Isterkh\QueryBuilder\Compilers\Compiler;
use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\QueryBuilder;

class PdoConnection implements ConnectionInterface
{
    public function __construct(
        protected Compiler $compiler,
        protected \PDO $pdo,
    ) {}

    /**
     * @return iterable<mixed>
     */
    public function query(QueryBuilder $query): iterable
    {
        $stmt = $this->executeQuery($query);
        if ($query->isLazy()) {
            return $this->lazyFetch($stmt);
        }

        return $stmt->fetchAll();
    }

    public function execute(QueryBuilder $query): int
    {
        $stmt = $this->executeQuery($query);

        return $stmt->rowCount();
    }

    public function getCompiled(QueryBuilder $query): Expression
    {
        return $this->compiler->compile($query);
    }

    protected function executeQuery(QueryBuilder $query): \PDOStatement
    {
        $compiled = $this->compiler->compile($query);
        $stmt = $this->pdo->prepare($query->toSql() ?? '');
        $stmt->execute($compiled->getBindings());

        return $stmt;
    }

    protected function lazyFetch(\PDOStatement $stmt): \Generator
    {
        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }
}
