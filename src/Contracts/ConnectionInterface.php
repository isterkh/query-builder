<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Contracts;

use Isterkh\QueryBuilder\Expressions\Expression;

interface ConnectionInterface
{
    public function getCompiler(): CompilerInterface;
    public function query(QueryInterface $query): iterable;
    public function execute(QueryInterface $query): int;
    public function getCompiled(QueryInterface $query): Expression;

}