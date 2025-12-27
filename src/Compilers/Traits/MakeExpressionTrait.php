<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers\Traits;

use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\Expressions\ExpressionBuilder;

trait MakeExpressionTrait
{
    /**
     * @param null|mixed[]|string $source
     */
    public function makeExpression(
        array|string|null $source,
        string $separator = ' ',
        ?\Closure $formatted = null,
        ?string $prefix = null,
        ?string $suffix = null
    ): Expression {
        if (empty($source)) {
            return new Expression('');
        }

        $builder = new ExpressionBuilder($prefix, $suffix, $separator);

        if (is_string($source)) {
            return $builder->add($source)->get();
        }

        foreach ($source as $key => $item) {
            if (empty($item)) {
                continue;
            }
            if ($item instanceof Expression) {
                $builder->add($item->getSql(), $item->getBindings());

                continue;
            }
            if ($formatted) {
                @[$sql, $binds] = $formatted($key, $item);
                $builder->add($sql ?: '', $binds ?? []);

                continue;
            }
            $item = is_array($item) ? $item : [$item];
            if (is_string($key)) {
                $builder->add("{$key} = ?", $item);

                continue;
            }
            $builder->add("{$key}", $item);
        }

        return $builder->get();
    }
}
