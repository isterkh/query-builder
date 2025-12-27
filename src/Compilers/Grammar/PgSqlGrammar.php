<?php

declare(strict_types=1);

namespace Isterkh\QueryBuilder\Compilers\Grammar;

use Isterkh\QueryBuilder\Contracts\GrammarInterface;

class PgSqlGrammar implements GrammarInterface
{
    public function wrap(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
