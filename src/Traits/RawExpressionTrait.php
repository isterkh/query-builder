<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Traits;

use Isterkh\QueryBuilder\Expressions\Expression;

trait RawExpressionTrait
{
    /**
     * @param string $sql
     * @param array<int, mixed> $bindings
     * @return Expression
     */
    protected function rawExpression(string $sql, array $bindings = []): Expression
    {
        return new Expression($sql, $bindings);
    }
}