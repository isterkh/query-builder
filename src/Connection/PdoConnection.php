<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Connection;

use Isterkh\QueryBuilder\Contracts\CompilerInterface;
use Isterkh\QueryBuilder\Contracts\ConnectionInterface;
use Isterkh\QueryBuilder\Contracts\QueryInterface;
use Isterkh\QueryBuilder\Expressions\Expression;

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

    public function query(QueryInterface $query, bool $lazy = false): iterable
    {
        $compiled = $this->compiler->compile($query);
        $stmt = $this->pdo->prepare($compiled->getSql());
        $stmt->execute($compiled->getBindings());
        if (!$lazy) {
            return $stmt->fetchAll();
        }
        return $this->lazyFetch($stmt);
    }

    protected function lazyFetch(\PDOStatement $stmt): \Generator
    {
        while ($row = $stmt->fetch()) {
            yield $row;
        }
    }

    public function execute(QueryInterface $query): int
    {
        // TODO: Implement execute() method.
    }

    public function getCompiled(QueryInterface $query): Expression
    {
        return $this->compiler->compile($query);
    }
}