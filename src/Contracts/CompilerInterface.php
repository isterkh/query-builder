<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Contracts;

use Isterkh\QueryBuilder\Expressions\Expression;

interface CompilerInterface
{
    public function compile(QueryInterface $query): Expression;
    public function supports(QueryInterface $query): bool;
}