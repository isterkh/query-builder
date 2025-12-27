<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder;

use Isterkh\QueryBuilder\Compilers\Compiler;
use Isterkh\QueryBuilder\Components\Expression;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;

class Connection implements ConnectionInterface
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

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollback(): bool
    {
        return $this->pdo->rollBack();
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
