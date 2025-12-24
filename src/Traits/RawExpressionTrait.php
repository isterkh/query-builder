<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Traits;

use Isterkh\QueryBuilder\Expressions\Expression;

trait RawExpressionTrait
{
    /**
     * @param array<int, mixed> $bindings
     */
    protected function rawExpression(string $sql, array $bindings = []): Expression
    {
        return new Expression($sql, $bindings);
    }
}
