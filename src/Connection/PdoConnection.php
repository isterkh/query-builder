<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Connection;

use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\Contracts\LazyQueryInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Expressions\Expression;
use MongoDB\Driver\Query;
use PDOStatement;

class PdoConnection implements ConnectionInterface
{


    public function __construct(
        protected CompilerInterface $compiler,
        protected \PDO              $pdo,
    )
    {
    }

    public function getCompiler(): CompilerInterface
    {
        return $this->compiler;
    }

    /**
     * @param QueryInterface $query
     * @return iterable<mixed>
     */
    public function query(QueryInterface $query): iterable
    {
        $stmt = $this->executeQuery($query);
        if ($query instanceof LazyQueryInterface && $query->isLazy()) {
            return $this->lazyFetch($stmt);
        }
        return $stmt->fetchAll();

    }

    protected function executeQuery(QueryInterface $query): PDOStatement
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

    public function execute(QueryInterface $query): int
    {
        $stmt = $this->executeQuery($query);
        return $stmt->rowCount();
    }

    public function getCompiled(QueryInterface $query): Expression
    {
        return $this->compiler->compile($query);
    }
}