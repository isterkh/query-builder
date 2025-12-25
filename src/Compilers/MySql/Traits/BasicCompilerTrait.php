<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers\MySql\Traits;

use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\ValueObjects\TableReference;

trait BasicCompilerTrait
{
    use WrapColumnsTrait;
    use MakeExpressionTrait;

    protected function compileTable(TableReference $from): string
    {
        $table = $this->wrap($from->getTable());
        if (!empty($from->getAlias())) {
            $table .= (' as ' . $this->wrap($from->getAlias()));
        }

        return $table;
    }

    // @param array<null|Expression> $expressions
}
