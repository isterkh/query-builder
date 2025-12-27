<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers\MySql\Traits;

use Isterkh\QueryBuilder\Expressions\Expression;
use Isterkh\QueryBuilder\ValueObjects\TableReference;

trait BasicCompilerTrait
{
    use WrapColumnsTrait;
    use MakeExpressionTrait;



    // @param array<null|Expression> $expressions
}
