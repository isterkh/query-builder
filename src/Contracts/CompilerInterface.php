<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Contracts;

use Isterkh\QueryBuilder\Compilers\DTO\CompiledQuery;

interface CompilerInterface
{
    public function compile(QueryInterface $query): CompiledQuery;
    public function supports(QueryInterface $query): bool;
}