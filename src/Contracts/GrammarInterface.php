<?php
declare(strict_types=1);


namespace Isterkh\QueryBuilder\Contracts;

interface GrammarInterface
{
    public function wrap(string $identifier): string;
}